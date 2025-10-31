<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\LearnProgress;

/**
 * @extends ServiceEntityRepository<LearnProgress>
 */
#[AsRepository(entityClass: LearnProgress::class)]
class LearnProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnProgress::class);
    }

    /**
     * 查找用户在课程中的学习进度
     *
     * @return array<LearnProgress>
     */
    public function findByUserAndCourse(string $userId, string $courseId): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('lp.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找用户在特定课时的学习进度
     */
    public function findByUserAndLesson(string $userId, string $lessonId): ?LearnProgress
    {
        $result = $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.lesson = :lessonId')
            ->setParameter('userId', $userId)
            ->setParameter('lessonId', $lessonId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof LearnProgress || null === $result);

        return $result;
    }

    /**
     * 查找已完成的学习进度
     *
     * @return array<LearnProgress>
     */
    public function findCompletedByUser(string $userId): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.isCompleted = true')
            ->setParameter('userId', $userId)
            ->orderBy('lp.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计用户的课程完成情况
     *
     * @return array<string, mixed>
     */
    public function getCourseCompletionStats(string $userId, string $courseId): array
    {
        /** @var array<string, mixed> */
        return $this->createQueryBuilder('lp')
            ->select('COUNT(lp.id) as totalLessons, SUM(CASE WHEN lp.isCompleted = true THEN 1 ELSE 0 END) as completedLessons, AVG(lp.progress) as avgProgress')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleResult()
        ;
    }

    /**
     * 查找需要同步的进度记录
     *
     * @return array<LearnProgress>
     */
    public function findNeedingSync(\DateTimeInterface $lastSyncTime): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.lastUpdateTime > :lastSyncTime')
            ->setParameter('lastSyncTime', $lastSyncTime)
            ->orderBy('lp.lastUpdateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找低质量学习记录
     *
     * @return array<LearnProgress>
     */
    public function findLowQualityProgress(float $qualityThreshold = 5.0): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.qualityScore < :threshold')
            ->andWhere('lp.qualityScore IS NOT NULL')
            ->setParameter('threshold', $qualityThreshold)
            ->orderBy('lp.qualityScore', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计学习效率分布
     *
     * @return array<string, mixed>
     */
    public function getLearningEfficiencyStats(): array
    {
        /** @var array<string, mixed> */
        return $this->createQueryBuilder('lp')
            ->select('
                COUNT(lp.id) as totalRecords,
                AVG(lp.effectiveDuration / lp.watchedDuration) as avgEfficiency,
                MIN(lp.effectiveDuration / lp.watchedDuration) as minEfficiency,
                MAX(lp.effectiveDuration / lp.watchedDuration) as maxEfficiency
            ')
            ->andWhere('lp.watchedDuration > 0')
            ->getQuery()
            ->getSingleResult()
        ;
    }

    /**
     * 查找最近更新的进度记录
     *
     * @return array<LearnProgress>
     */
    public function findRecentlyUpdated(int $limit = 50): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->orderBy('lp.lastUpdateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 批量更新有效学习时长
     *
     * @param array<int> $progressIds
     * @param array<float> $effectiveDurations
     */
    public function batchUpdateEffectiveDuration(array $progressIds, array $effectiveDurations): void
    {
        foreach ($progressIds as $index => $progressId) {
            $effectiveDuration = $effectiveDurations[$index];

            $this->createQueryBuilder('lp')
                ->update()
                ->set('lp.effectiveDuration', ':effectiveDuration')
                ->andWhere('lp.id = :progressId')
                ->setParameter('effectiveDuration', $effectiveDuration)
                ->setParameter('progressId', $progressId)
                ->getQuery()
                ->execute()
            ;
        }
    }

    /**
     * 更新学习进度
     */
    public function updateProgress(LearnProgress $progress, bool $flush = true): void
    {
        $this->getEntityManager()->persist($progress);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据课程查找学习进度
     *
     * @return array<LearnProgress>
     */
    public function findByCourse(string $courseId): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户查找学习进度
     *
     * @return array<LearnProgress>
     */
    public function findByUser(string $userId): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户和日期范围查找学习进度
     *
     * @return array<LearnProgress>
     */
    public function findByUserAndDateRange(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnProgress> */
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.createTime >= :startDate')
            ->andWhere('lp.createTime <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按过滤条件计算完成率
     *
     * @param array<string, mixed> $filters
     */
    public function calculateCompletionRateByFilters(array $filters): float
    {
        $qb = $this->createQueryBuilder('lp')
            ->select('COUNT(lp.id) as total, SUM(CASE WHEN lp.isCompleted = true THEN 1 ELSE 0 END) as completed')
        ;

        if (isset($filters['courseId'])) {
            $qb->andWhere('lp.course = :courseId')
                ->setParameter('courseId', $filters['courseId'])
            ;
        }

        if (isset($filters['userId'])) {
            $qb->andWhere('lp.userId = :userId')
                ->setParameter('userId', $filters['userId'])
            ;
        }

        /** @var array{total: int, completed: int}|null $result */
        $result = $qb->getQuery()->getSingleResult();

        if (!is_array($result) || 0 === $result['total']) {
            return 0.0;
        }

        return (float) ($result['completed'] / $result['total']) * 100;
    }

    /**
     * 按日期范围和过滤条件查找进度
     *
     * @param array<string, mixed> $filters
     * @return array<LearnProgress>
     */
    public function findByDateRangeAndFilters(\DateTimeInterface $startDate, \DateTimeInterface $endDate, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('lp')
            ->where('lp.createTime >= :startDate')
            ->andWhere('lp.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
        ;

        if (isset($filters['courseId'])) {
            $qb->andWhere('lp.course = :courseId')
                ->setParameter('courseId', $filters['courseId'])
            ;
        }

        if (isset($filters['userId'])) {
            $qb->andWhere('lp.userId = :userId')
                ->setParameter('userId', $filters['userId'])
            ;
        }

        /** @var array<LearnProgress> */
        return $qb->orderBy('lp.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按日期范围统计完成数
     */
    public function countCompletionsByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $result = $this->createQueryBuilder('lp')
            ->select('COUNT(lp.id)')
            ->where('lp.isCompleted = :completed')
            ->andWhere('lp.createTime >= :startDate')
            ->andWhere('lp.createTime <= :endDate')
            ->setParameter('completed', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 保存实体
     */
    public function save(LearnProgress $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnProgress $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
