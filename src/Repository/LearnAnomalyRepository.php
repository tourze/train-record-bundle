<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * @method LearnAnomaly|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnAnomaly|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnAnomaly[]    findAll()
 * @method LearnAnomaly[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnAnomalyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnAnomaly::class);
    }

    /**
     * 查找会话的异常记录
     */
    public function findBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('la.detectedTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找未处理的异常
     */
    public function findUnprocessed(): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.status = :detected OR la.status = :investigating')
            ->setParameter('detected', AnomalyStatus::DETECTED)
            ->setParameter('investigating', AnomalyStatus::INVESTIGATING)
            ->orderBy('la.severity', 'DESC')
            ->addOrderBy('la.detectedTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找高优先级异常
     */
    public function findHighPriority(): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.severity = :high OR la.severity = :critical')
            ->andWhere('la.status != :resolved AND la.status != :ignored')
            ->setParameter('high', AnomalySeverity::HIGH)
            ->setParameter('critical', AnomalySeverity::CRITICAL)
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->setParameter('ignored', AnomalyStatus::IGNORED)
            ->orderBy('la.severity', 'DESC')
            ->addOrderBy('la.detectedTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按类型统计异常
     */
    public function getAnomalyStatsByType(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('la')
            ->select('la.anomalyType, COUNT(la.id) as count')
            ->andWhere('la.detectedTime >= :startDate')
            ->andWhere('la.detectedTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('la.anomalyType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按严重程度统计异常
     */
    public function getAnomalyStatsBySeverity(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('la')
            ->select('la.severity, COUNT(la.id) as count')
            ->andWhere('la.detectedTime >= :startDate')
            ->andWhere('la.detectedTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('la.severity')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找自动检测的异常
     */
    public function findAutoDetected(int $limit = 100): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.isAutoDetected = true')
            ->orderBy('la.detectedTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找特定类型的异常
     */
    public function findByType(AnomalyType $type, int $limit = 50): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.anomalyType = :type')
            ->setParameter('type', $type)
            ->orderBy('la.detectedTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找处理时间过长的异常
     */
    public function findLongProcessing(\DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.detectedTime < :threshold')
            ->andWhere('la.status = :detected OR la.status = :investigating')
            ->setParameter('threshold', $threshold)
            ->setParameter('detected', AnomalyStatus::DETECTED)
            ->setParameter('investigating', AnomalyStatus::INVESTIGATING)
            ->orderBy('la.detectedTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计处理效率
     */
    public function getProcessingEfficiencyStats(): array
    {
        return $this->createQueryBuilder('la')
            ->select('
                COUNT(la.id) as totalAnomalies,
                SUM(CASE WHEN la.status = :resolved THEN 1 ELSE 0 END) as resolvedCount,
                SUM(CASE WHEN la.status = :ignored THEN 1 ELSE 0 END) as ignoredCount,
                AVG(CASE WHEN la.resolvedTime IS NOT NULL AND la.detectedTime IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, la.detectedTime, la.resolvedTime) 
                    ELSE NULL END) as avgProcessingTime
            ')
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->setParameter('ignored', AnomalyStatus::IGNORED)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * 查找最近的异常趋势
     */
    public function getRecentTrends(int $days = 7): array
    {
        $startDate = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('la')
            ->select('DATE(la.detectedTime) as date, COUNT(la.id) as count')
            ->andWhere('la.detectedTime >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('DATE(la.detectedTime)')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 