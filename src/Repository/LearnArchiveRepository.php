<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

/**
 * @method LearnArchive|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnArchive|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnArchive[]    findAll()
 * @method LearnArchive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnArchive::class);
    }

    /**
     * 查找用户的档案
     */
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找用户和课程的档案
     */
    public function findByUserAndCourse(string $userId, string $courseId): ?LearnArchive
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.userId = :userId')
            ->andWhere('la.courseId = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找已过期的档案
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.expiryDate < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('la.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据状态查找档案
     */
    public function findByStatus(ArchiveStatus $status): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.archiveStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计档案信息
     */
    public function getArchiveStats(): array
    {
        return $this->createQueryBuilder('la')
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
            ->getSingleResult();
    }

    /**
     * 查找需要验证的档案
     */
    public function findNeedVerification(): array
    {
        $oneMonthAgo = new \DateTimeImmutable('-1 month');
        
        return $this->createQueryBuilder('la')
            ->where('la.lastVerificationTime IS NULL OR la.lastVerificationTime < :oneMonthAgo')
            ->setParameter('oneMonthAgo', $oneMonthAgo)
            ->orderBy('la.lastVerificationTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找即将过期的档案
     */
    public function findExpiringSoon(int $days = 30): array
    {
        $expiryDate = new \DateTimeImmutable("+{$days} days");
        
        return $this->createQueryBuilder('la')
            ->where('la.expiryDate <= :expiryDate')
            ->andWhere('la.archiveStatus != :expired')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('expired', ArchiveStatus::EXPIRED)
            ->orderBy('la.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已过期的档案
     */
    public function findExpiredArchives(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('la')
            ->where('la.expiryDate < :now')
            ->andWhere('la.archiveStatus != :expired')
            ->setParameter('now', $now)
            ->setParameter('expired', ArchiveStatus::EXPIRED)
            ->getQuery()
            ->getResult();
    }

    /**
     * 按状态统计档案数量
     */
    public function countByStatus(ArchiveStatus $status): int
    {
        return $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.archiveStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 获取格式分布
     */
    public function getFormatDistribution(): array
    {
        return $this->createQueryBuilder('la')
            ->select('la.archiveFormat as format, COUNT(la.id) as count')
            ->groupBy('la.archiveFormat')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取每月归档数量
     */
    public function getMonthlyArchiveCount(int $months = 12): array
    {
        $startDate = new \DateTimeImmutable("-{$months} months");
        
        return $this->createQueryBuilder('la')
            ->select("DATE_FORMAT(la.archiveDate, '%Y-%m') as month, COUNT(la.id) as count")
            ->where('la.archiveDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
