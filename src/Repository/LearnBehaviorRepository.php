<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;

/**
 * @extends ServiceEntityRepository<LearnBehavior>
 */
#[AsRepository(entityClass: LearnBehavior::class)]
class LearnBehaviorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnBehavior::class);
    }

    /**
     * 查找会话的可疑行为
     *
     * @return array<LearnBehavior>
     */
    public function findSuspiciousBySession(string $sessionId): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->andWhere('lb.isSuspicious = true')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计会话的行为类型分布
     *
     * @return array<array{behaviorType: mixed, count: int}>
     */
    public function getBehaviorStatsBySession(string $sessionId): array
    {
        /** @var array<array{behaviorType: mixed, count: int}> */
        return $this->createQueryBuilder('lb')
            ->select('lb.behaviorType, COUNT(lb.id) as count')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->groupBy('lb.behaviorType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找时间范围内的行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findByTimeRange(\DateTimeInterface $startTime, \DateTimeInterface $endTime): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startTime')
            ->andWhere('lb.createTime <= :endTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找设备的行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findByDeviceFingerprint(string $deviceFingerprint, int $limit = 100): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.deviceFingerprint = :deviceFingerprint')
            ->setParameter('deviceFingerprint', $deviceFingerprint)
            ->orderBy('lb.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据日期范围查找行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据日期范围查找可疑行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findSuspiciousByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->andWhere('lb.isSuspicious = true')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找会话的最近行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findRecentBySession(string $sessionId, int $limit = 10): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找用户在指定日期范围内的行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findByUserAndDateRange(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.userId = :userId')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定会话的所有行为记录
     *
     * @return array<LearnBehavior>
     */
    public function findBySession(string $sessionId): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户ID和课程ID查找学习行为
     *
     * @return array<LearnBehavior>
     */
    public function findByUserAndCourse(string $userId, string $courseId): array
    {
        /** @var array<LearnBehavior> */
        return $this->createQueryBuilder('lb')
            ->leftJoin('lb.session', 's')
            ->leftJoin('s.registration', 'r')
            ->leftJoin('s.lesson', 'l')
            ->leftJoin('l.chapter', 'ch')
            ->leftJoin('ch.course', 'c')
            ->where('r.student = :userId')
            ->andWhere('c.id = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('lb.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按日期范围和过滤条件查找行为
     *
     * @param array<string, mixed> $filters
     * @return array<LearnBehavior>
     */
    public function findByDateRangeAndFilters(\DateTimeInterface $startDate, \DateTimeInterface $endDate, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('lb')
            ->where('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
        ;

        if (isset($filters['courseId'])) {
            $qb->leftJoin('lb.session', 's')
                ->leftJoin('s.lesson', 'l')
                ->leftJoin('l.chapter', 'ch')
                ->leftJoin('ch.course', 'c')
                ->andWhere('c.id = :courseId')
                ->setParameter('courseId', $filters['courseId'])
            ;
        }

        if (isset($filters['userId'])) {
            $qb->leftJoin('lb.session', 's2')
                ->leftJoin('s2.registration', 'r')
                ->andWhere('r.userId = :userId')
                ->setParameter('userId', $filters['userId'])
            ;
        }

        /** @var array<LearnBehavior> */
        return $qb->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(LearnBehavior $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnBehavior $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
