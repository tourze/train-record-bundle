<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnDevice;

/**
 * @method LearnDevice|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnDevice|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnDevice[]    findAll()
 * @method LearnDevice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnDevice::class);
    }

    /**
     * 查找用户的活跃设备
     */
    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isActive = true')
            ->andWhere('ld.isBlocked = false')
            ->setParameter('userId', $userId)
            ->orderBy('ld.lastUsedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据设备指纹查找设备
     */
    public function findByFingerprint(string $deviceFingerprint): ?LearnDevice
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.deviceFingerprint = :deviceFingerprint')
            ->setParameter('deviceFingerprint', $deviceFingerprint)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找用户的可信设备
     */
    public function findTrustedByUser(string $userId): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isTrusted = true')
            ->andWhere('ld.isActive = true')
            ->setParameter('userId', $userId)
            ->orderBy('ld.lastUsedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找被阻止的设备
     */
    public function findBlockedDevices(): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.isBlocked = true')
            ->orderBy('ld.lastUsedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计用户的设备数量
     */
    public function countByUser(string $userId): int
    {
        return $this->createQueryBuilder('ld')
            ->select('COUNT(ld.id)')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isActive = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 查找长时间未使用的设备
     */
    public function findInactiveDevices(\DateTimeInterface $beforeDate): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.lastUsedTime < :beforeDate')
            ->andWhere('ld.isActive = true')
            ->setParameter('beforeDate', $beforeDate)
            ->orderBy('ld.lastUsedTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 更新设备最后使用时间
     */
    public function updateLastUsedTime(string $deviceId): void
    {
        $this->createQueryBuilder('ld')
            ->update()
            ->set('ld.lastUsedTime', ':now')
            ->set('ld.usageCount', 'ld.usageCount + 1')
            ->andWhere('ld.id = :deviceId')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('deviceId', $deviceId)
            ->getQuery()
            ->execute();
    }

    /**
     * 查找活跃的设备
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.isActive = true')
            ->andWhere('ld.isBlocked = false')
            ->orderBy('ld.lastUsedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据最后使用时间查找设备
     */
    public function findByLastSeenAfter(\DateTimeInterface $afterDate): array
    {
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.lastUsedTime > :afterDate')
            ->setParameter('afterDate', $afterDate)
            ->orderBy('ld.lastUsedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
