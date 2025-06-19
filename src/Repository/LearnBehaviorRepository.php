<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;

/**
 * @method LearnBehavior|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnBehavior|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnBehavior[]    findAll()
 * @method LearnBehavior[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnBehaviorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnBehavior::class);
    }

    /**
     * 查找会话的可疑行为
     */
    public function findSuspiciousBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->andWhere('lb.isSuspicious = true')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计会话的行为类型分布
     */
    public function getBehaviorStatsBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('lb')
            ->select('lb.behaviorType, COUNT(lb.id) as count')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->groupBy('lb.behaviorType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找时间范围内的行为记录
     */
    public function findByTimeRange(\DateTimeInterface $startTime, \DateTimeInterface $endTime): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startTime')
            ->andWhere('lb.createTime <= :endTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找设备的行为记录
     */
    public function findByDeviceFingerprint(string $deviceFingerprint, int $limit = 100): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.deviceFingerprint = :deviceFingerprint')
            ->setParameter('deviceFingerprint', $deviceFingerprint)
            ->orderBy('lb.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据日期范围查找行为记录
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据日期范围查找可疑行为记录
     */
    public function findSuspiciousByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->andWhere('lb.isSuspicious = true')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找会话的最近行为记录
     */
    public function findRecentBySession(string $sessionId, int $limit = 10): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找用户在指定日期范围内的行为记录
     */
    public function findByUserAndDateRange(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.userId = :userId')
            ->andWhere('lb.createTime >= :startDate')
            ->andWhere('lb.createTime <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定会话的所有行为记录
     */
    public function findBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('lb')
            ->andWhere('lb.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('lb.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 