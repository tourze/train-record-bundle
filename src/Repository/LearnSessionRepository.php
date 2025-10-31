<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * @extends ServiceEntityRepository<LearnSession>
 */
#[AsRepository(entityClass: LearnSession::class)]
class LearnSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnSession::class);
    }

    /**
     * 查找学员的活跃学习会话
     *
     * @param mixed $student 学员实体
     *
     * @return array<LearnSession>
     */
    public function findActiveSessionsByStudent(mixed $student): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('finished', false)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找学员在其他课程的活跃会话
     *
     * @param mixed $student      学员实体
     * @param mixed $currentLesson 当前课时（可以是 Lesson 实体或 ID）
     *
     * @return array<LearnSession>
     */
    public function findOtherActiveSessionsByStudent(mixed $student, mixed $currentLesson): array
    {
        $qb = $this->createQueryBuilder('ls')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('finished', false)
        ;

        // 根据传入的参数类型决定比较方式
        if ($currentLesson instanceof Lesson) {
            $qb->andWhere('ls.lesson != :currentLesson')
                ->setParameter('currentLesson', $currentLesson)
            ;
        } else {
            // 如果是字符串或其他类型，通过 lesson 的 ID 来比较
            $qb->andWhere('IDENTITY(ls.lesson) != :currentLessonId')
                ->setParameter('currentLessonId', $currentLesson)
            ;
        }

        /** @var array<LearnSession> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 将学员的所有活跃会话设置为非活跃
     *
     * @param mixed $student 学员实体
     *
     * @return int 更新的记录数
     */
    public function deactivateAllActiveSessionsByStudent(mixed $student): int
    {
        $result = $this->createQueryBuilder('ls')
            ->update()
            ->set('ls.active', ':inactive')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('inactive', false)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result) || is_string($result));

        return is_int($result) ? $result : (int) $result;
    }

    /**
     * 查找超时的活跃会话（指定分钟内未更新）
     *
     * @param int $thresholdMinutes 超时阈值（分钟）
     *
     * @return array<LearnSession>
     */
    public function findInactiveActiveSessions(int $thresholdMinutes): array
    {
        $thresholdTime = new \DateTimeImmutable("-{$thresholdMinutes} minutes");

        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->andWhere('ls.lastLearnTime < :thresholdTime')
            ->setParameter('active', true)
            ->setParameter('finished', false)
            ->setParameter('thresholdTime', $thresholdTime)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 批量更新会话活跃状态
     *
     * @param array<string> $sessionIds 会话 ID数组
     * @param bool          $active     活跃状态
     *
     * @return int 更新的记录数
     */
    public function batchUpdateActiveStatus(array $sessionIds, bool $active): int
    {
        if ([] === $sessionIds) {
            return 0;
        }

        $result = $this->createQueryBuilder('ls')
            ->update()
            ->set('ls.active', ':active')
            ->where('ls.id IN (:ids)')
            ->setParameter('active', $active)
            ->setParameter('ids', $sessionIds)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result) || is_string($result));

        return is_int($result) ? $result : (int) $result;
    }

    /**
     * 根据用户和日期范围查找会话
     *
     * @param string             $userId    用户ID
     * @param \DateTimeInterface $startDate 开始日期
     * @param \DateTimeInterface $endDate   结束日期
     *
     * @return array<LearnSession>
     */
    public function findByUserAndDateRange(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->leftJoin('ls.student', 's')
            ->where('s.id = :userId')
            ->andWhere('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据日期范围查找会话
     *
     * @param \DateTimeInterface $startDate 开始日期
     * @param \DateTimeInterface $endDate   结束日期
     *
     * @return array<LearnSession>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已完成的会话
     *
     * @return array<LearnSession>
     */
    public function findCompletedSessions(): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.finished = :finished')
            ->setParameter('finished', true)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找活跃的会话
     *
     * @return array<LearnSession>
     */
    public function findActiveSessions(): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->setParameter('active', true)
            ->setParameter('finished', false)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户和课时查找会话
     *
     * @param string $userId   用户ID
     * @param string $lessonId 课时ID
     *
     * @return array<LearnSession>
     */
    public function findByUserAndLesson(string $userId, string $lessonId): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->leftJoin('ls.student', 's')
            ->where('s.id = :userId')
            ->andWhere('ls.lesson = :lessonId')
            ->setParameter('userId', $userId)
            ->setParameter('lessonId', $lessonId)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 刷新实体管理器
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * 根据用户ID和课程ID查找学习会话
     *
     * @return array<LearnSession>
     */
    public function findByUserAndCourse(string $userId, string $courseId): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->leftJoin('ls.registration', 'r')
            ->leftJoin('ls.lesson', 'l')
            ->leftJoin('l.chapter', 'ch')
            ->leftJoin('ch.course', 'c')
            ->where('r.student = :userId')
            ->andWhere('c.id = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('ls.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已过期的会话
     *
     * @return array<LearnSession>
     */
    public function findExpiredSessions(\DateTimeInterface $expireTime): array
    {
        /** @var array<LearnSession> */
        return $this->createQueryBuilder('ls')
            ->where('ls.lastLearnTime IS NOT NULL')
            ->andWhere('ls.lastLearnTime < :expireTime')
            ->andWhere('ls.finished = false')
            ->setParameter('expireTime', $expireTime)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按过滤条件计算平均时长
     *
     * @param array<string, mixed> $filters
     */
    public function avgDurationByFilters(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('AVG(ls.totalDuration) as avgDuration')
        ;

        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    /**
     * 统计指定时间后的活跃会话数
     */
    public function countActiveSessionsSince(\DateTimeInterface $since): int
    {
        $result = $this->createQueryBuilder('ls')
            ->select('COUNT(ls.id)')
            ->where('ls.createTime >= :since')
            ->andWhere('ls.active = true')
            ->andWhere('ls.finished = false')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 应用过滤条件
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['startTime'])) {
            $qb->andWhere('ls.createTime >= :startTime')
                ->setParameter('startTime', $filters['startTime'])
            ;
        }

        if (isset($filters['endTime'])) {
            $qb->andWhere('ls.createTime <= :endTime')
                ->setParameter('endTime', $filters['endTime'])
            ;
        }

        if (isset($filters['courseId'])) {
            $qb->leftJoin('ls.lesson', 'l')
                ->leftJoin('l.chapter', 'ch')
                ->leftJoin('ch.course', 'c')
                ->andWhere('c.id = :courseId')
                ->setParameter('courseId', $filters['courseId'])
            ;
        }
    }

    /**
     * 按过滤条件统计会话数
     *
     * @param array<string, mixed> $filters
     */
    public function countByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('COUNT(ls.id)')
        ;

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 按过滤条件统计唯一课程数
     *
     * @param array<string, mixed> $filters
     */
    public function countUniqueCoursesByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('ls.lesson', 'l')
            ->leftJoin('l.chapter', 'ch')
            ->leftJoin('ch.course', 'c')
        ;

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 按过滤条件统计总时长
     *
     * @param array<string, mixed> $filters
     */
    public function sumDurationByFilters(array $filters): float
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('SUM(ls.totalDuration)')
        ;

        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : 0.0;
    }

    /**
     * 按过滤条件统计活跃用户数
     *
     * @param array<string, mixed> $filters
     */
    public function countActiveUsersByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT s.id)')
            ->leftJoin('ls.registration', 'r')
            ->leftJoin('r.student', 's')
            ->where('ls.active = true')
        ;

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 按过滤条件统计新用户数
     *
     * @param array<string, mixed> $filters
     */
    public function countNewUsersByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT s.id)')
            ->leftJoin('ls.registration', 'r')
            ->leftJoin('r.student', 's')
        ;

        // 只统计首次学习的用户
        if (isset($filters['startTime'])) {
            $subQb = $this->createQueryBuilder('ls2')
                ->select('s2.id')
                ->leftJoin('ls2.registration', 'r2')
                ->leftJoin('r2.student', 's2')
                ->where('ls2.createTime < :startTime')
                ->groupBy('s2.id')
            ;

            $qb->where($qb->expr()->notIn('s.id', $subQb->getDQL()));
        }

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 按日期范围和过滤条件查找会话
     *
     * @param array<string, mixed> $filters
     * @return array<LearnSession>
     */
    public function findByDateRangeAndFilters(\DateTimeInterface $startDate, \DateTimeInterface $endDate, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('ls')
            ->where('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
        ;

        $this->applyFilters($qb, $filters);

        /** @var array<LearnSession> */
        return $qb->orderBy('ls.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按日期范围统计会话数量
     */
    public function countByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $result = $this->createQueryBuilder('ls')
            ->select('COUNT(ls.id)')
            ->where('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 按日期范围统计唯一用户数
     */
    public function countUniqueUsersByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $result = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT ls.student)')
            ->where('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 按日期范围统计总时长
     */
    public function sumDurationByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $result = $this->createQueryBuilder('ls')
            ->select('SUM(ls.totalDuration)')
            ->where('ls.createTime >= :startDate')
            ->andWhere('ls.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0);
    }

    /**
     * 按过滤条件统计唯一用户数
     *
     * @param array<string, mixed> $filters
     */
    public function countUniqueUsersByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT ls.student)')
        ;

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 获取当前在线用户数
     */
    public function getCurrentOnlineUsers(): int
    {
        $threshold = new \DateTimeImmutable('-15 minutes');

        $result = $this->createQueryBuilder('ls')
            ->select('COUNT(DISTINCT ls.student)')
            ->where('ls.active = :active')
            ->andWhere('ls.lastLearnTime >= :threshold')
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 保存实体
     */
    public function save(LearnSession $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnSession $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
