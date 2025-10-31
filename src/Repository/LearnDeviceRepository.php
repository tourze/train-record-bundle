<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\LearnDevice;

/**
 * @extends ServiceEntityRepository<LearnDevice>
 */
#[AsRepository(entityClass: LearnDevice::class)]
class LearnDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnDevice::class);
    }

    /**
     * 查找用户的活跃设备
     *
     * @return array<LearnDevice>
     */
    public function findActiveByUser(string $userId, ?\DateTimeInterface $threshold = null): array
    {
        $qb = $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isActive = true')
            ->andWhere('ld.isBlocked = false')
            ->setParameter('userId', $userId)
        ;

        if (null !== $threshold) {
            $qb->andWhere('ld.lastUseTime >= :threshold')
                ->setParameter('threshold', $threshold)
            ;
        }

        /** @var array<LearnDevice> */
        return $qb->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据设备指纹查找设备
     */
    public function findByFingerprint(string $deviceFingerprint): ?LearnDevice
    {
        $result = $this->createQueryBuilder('ld')
            ->andWhere('ld.deviceFingerprint = :deviceFingerprint')
            ->setParameter('deviceFingerprint', $deviceFingerprint)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof LearnDevice || null === $result);

        return $result;
    }

    /**
     * 查找用户的可信设备
     *
     * @return array<LearnDevice>
     */
    public function findTrustedByUser(string $userId): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isTrusted = true')
            ->andWhere('ld.isActive = true')
            ->setParameter('userId', $userId)
            ->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找被阻止的设备
     *
     * @return array<LearnDevice>
     */
    public function findBlockedDevices(): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.isBlocked = true')
            ->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计用户的设备数量
     */
    public function countByUser(string $userId): int
    {
        $result = $this->createQueryBuilder('ld')
            ->select('COUNT(ld.id)')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.isActive = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 查找长时间未使用的设备
     *
     * @return array<LearnDevice>
     */
    public function findInactiveDevices(\DateTimeInterface $beforeDate): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.lastUseTime < :beforeDate')
            ->andWhere('ld.isActive = true')
            ->setParameter('beforeDate', $beforeDate)
            ->orderBy('ld.lastUseTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 更新设备最后使用时间
     */
    public function updateLastUsedTime(string $deviceId): void
    {
        $this->createQueryBuilder('ld')
            ->update()
            ->set('ld.lastUseTime', ':now')
            ->set('ld.usageCount', 'ld.usageCount + 1')
            ->andWhere('ld.id = :deviceId')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('deviceId', $deviceId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 查找活跃的设备
     *
     * @return array<LearnDevice>
     */
    public function findActive(): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.isActive = true')
            ->andWhere('ld.isBlocked = false')
            ->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据最后使用时间查找设备
     *
     * @return array<LearnDevice>
     */
    public function findByLastSeenAfter(\DateTimeInterface $afterDate): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.lastUseTime > :afterDate')
            ->setParameter('afterDate', $afterDate)
            ->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户ID和设备指纹查找设备
     */
    public function findByUserAndFingerprint(string $userId, string $deviceFingerprint): ?LearnDevice
    {
        $result = $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->andWhere('ld.deviceFingerprint = :deviceFingerprint')
            ->setParameter('userId', $userId)
            ->setParameter('deviceFingerprint', $deviceFingerprint)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof LearnDevice || null === $result);

        return $result;
    }

    /**
     * 查找用户的所有设备
     *
     * @return array<LearnDevice>
     */
    public function findByUser(string $userId): array
    {
        /** @var array<LearnDevice> */
        return $this->createQueryBuilder('ld')
            ->andWhere('ld.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ld.lastUseTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(LearnDevice $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnDevice $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
