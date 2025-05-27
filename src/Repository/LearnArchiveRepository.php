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
     * 查找已过期的档案
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.expiryDate < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('la.expiryDate', 'ASC')
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
}
