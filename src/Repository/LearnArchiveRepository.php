<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

/**
 * @extends ServiceEntityRepository<LearnArchive>
 */
#[AsRepository(entityClass: LearnArchive::class)]
class LearnArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnArchive::class);
    }

    /**
     * 查找用户的档案
     *
     * @return array<LearnArchive>
     */
    public function findByUser(string $userId): array
    {
        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找用户和课程的档案
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?LearnArchive
    {
        /** @var LearnArchive|null */
        return $this->createQueryBuilder('la')
            ->andWhere('la.userId = :userId')
            ->andWhere('la.courseId = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找已过期的档案
     *
     * @return array<LearnArchive>
     */
    public function findExpired(): array
    {
        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.expiryTime < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('la.expiryTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据状态查找档案
     *
     * @return array<LearnArchive>
     */
    public function findByStatus(ArchiveStatus $status): array
    {
        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.archiveStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计档案信息
     *
     * @return array{totalArchives: int, activeCount: int, archivedCount: int, expiredCount: int}
     */
    public function getArchiveStats(): array
    {
        /** @var array{totalArchives: string|int, activeCount: string|int|null, archivedCount: string|int|null, expiredCount: string|int|null} $result */
        $result = $this->createQueryBuilder('la')
            ->select('
                COUNT(la.id) as totalArchives,
                SUM(CASE WHEN la.archiveStatus = :active THEN 1 ELSE 0 END) as activeCount,
                SUM(CASE WHEN la.archiveStatus = :archived THEN 1 ELSE 0 END) as archivedCount,
                SUM(CASE WHEN la.archiveStatus = :expired THEN 1 ELSE 0 END) as expiredCount
            ')
            ->setParameter('active', ArchiveStatus::ACTIVE)
            ->setParameter('archived', ArchiveStatus::ARCHIVED)
            ->setParameter('expired', ArchiveStatus::EXPIRED)
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'totalArchives' => (int) $result['totalArchives'],
            'activeCount' => (int) ($result['activeCount'] ?? 0),
            'archivedCount' => (int) ($result['archivedCount'] ?? 0),
            'expiredCount' => (int) ($result['expiredCount'] ?? 0),
        ];
    }

    /**
     * 查找需要验证的档案
     *
     * @return array<LearnArchive>
     */
    public function findNeedVerification(): array
    {
        $oneMonthAgo = new \DateTimeImmutable('-1 month');

        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->where('la.lastVerifyTime IS NULL OR la.lastVerifyTime < :oneMonthAgo')
            ->setParameter('oneMonthAgo', $oneMonthAgo)
            ->orderBy('la.lastVerifyTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将过期的档案
     *
     * @return array<LearnArchive>
     */
    public function findExpiringSoon(int $days = 30): array
    {
        $expiryDate = new \DateTimeImmutable("+{$days} days");

        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->where('la.expiryTime <= :expiryDate')
            ->andWhere('la.archiveStatus != :expired')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('expired', ArchiveStatus::EXPIRED)
            ->orderBy('la.expiryTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已过期的档案
     *
     * @return array<LearnArchive>
     */
    public function findExpiredArchives(): array
    {
        $now = new \DateTimeImmutable();

        /** @var array<LearnArchive> */
        return $this->createQueryBuilder('la')
            ->where('la.expiryTime < :now')
            ->andWhere('la.archiveStatus != :expired')
            ->setParameter('now', $now)
            ->setParameter('expired', ArchiveStatus::EXPIRED)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按状态统计档案数量
     */
    public function countByStatus(ArchiveStatus $status): int
    {
        $result = $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.archiveStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 获取格式分布
     *
     * @return array<array{format: string, count: int}>
     */
    public function getFormatDistribution(): array
    {
        /** @var array<array{format: string, count: int}> */
        return $this->createQueryBuilder('la')
            ->select('la.archiveFormat as format, COUNT(la.id) as count')
            ->groupBy('la.archiveFormat')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取每月归档数量
     *
     * @return array<array{month: string, count: int}>
     */
    public function getMonthlyArchiveCount(int $months = 12): array
    {
        $startDate = new \DateTimeImmutable("-{$months} months");

        /** @var array<array{month: string, count: string|int}> $result */
        $result = $this->createQueryBuilder('la')
            ->select("DATE_FORMAT(la.archiveTime, '%Y-%m') as month, COUNT(la.id) as count")
            ->where('la.archiveTime >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(fn ($item) => [
            'month' => $item['month'],
            'count' => (int) $item['count'],
        ], $result);
    }

    /**
     * 保存实体
     */
    public function save(LearnArchive $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnArchive $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
