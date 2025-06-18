<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

/**
 * @method LearnStatistics|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnStatistics|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnStatistics[]    findAll()
 * @method LearnStatistics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnStatistics::class);
    }

    /**
     * 按类型和周期查找统计
     */
    public function findByTypeAndPeriod(StatisticsType $type, StatisticsPeriod $period, int $limit = 30): array
    {
        return $this->createQueryBuilder('ls')
            ->andWhere('ls.statisticsType = :type')
            ->andWhere('ls.statisticsPeriod = :period')
            ->setParameter('type', $type)
            ->setParameter('period', $period)
            ->orderBy('ls.statisticsDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定日期范围的统计
     */
    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?StatisticsType $type = null,
        ?StatisticsPeriod $period = null
    ): array {
        $qb = $this->createQueryBuilder('ls')
            ->andWhere('ls.statisticsDate >= :startDate')
            ->andWhere('ls.statisticsDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($type !== null) {
            $qb->andWhere('ls.statisticsType = :type')
               ->setParameter('type', $type);
        }

        if ($period !== null) {
            $qb->andWhere('ls.statisticsPeriod = :period')
               ->setParameter('period', $period);
        }

        return $qb->orderBy('ls.statisticsDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 查找最新的统计记录
     */
    public function findLatestByType(StatisticsType $type, StatisticsPeriod $period): ?LearnStatistics
    {
        return $this->createQueryBuilder('ls')
            ->andWhere('ls.statisticsType = :type')
            ->andWhere('ls.statisticsPeriod = :period')
            ->setParameter('type', $type)
            ->setParameter('period', $period)
            ->orderBy('ls.statisticsDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找指定日期的统计
     */
    public function findByDate(\DateTimeInterface $date, ?StatisticsType $type = null): array
    {
        $qb = $this->createQueryBuilder('ls')
            ->andWhere('ls.statisticsDate = :date')
            ->setParameter('date', $date);

        if ($type !== null) {
            $qb->andWhere('ls.statisticsType = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('ls.statisticsType', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 获取统计概览
     */
    public function getStatisticsOverview(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('ls')
            ->select('
                ls.statisticsType,
                ls.statisticsPeriod,
                SUM(ls.totalUsers) as totalUsers,
                SUM(ls.activeUsers) as activeUsers,
                SUM(ls.totalSessions) as totalSessions,
                SUM(ls.totalDuration) as totalDuration,
                SUM(ls.effectiveDuration) as effectiveDuration,
                SUM(ls.anomalyCount) as anomalyCount,
                AVG(ls.completionRate) as avgCompletionRate,
                AVG(ls.averageEfficiency) as avgEfficiency
            ')
            ->andWhere('ls.statisticsDate = :date')
            ->setParameter('date', $date)
            ->groupBy('ls.statisticsType', 'ls.statisticsPeriod')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取趋势数据
     */
    public function getTrendData(
        StatisticsType $type,
        StatisticsPeriod $period,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('ls')
            ->select('
                ls.statisticsDate,
                ls.totalUsers,
                ls.activeUsers,
                ls.totalSessions,
                ls.totalDuration,
                ls.effectiveDuration,
                ls.anomalyCount,
                ls.completionRate,
                ls.averageEfficiency
            ')
            ->andWhere('ls.statisticsType = :type')
            ->andWhere('ls.statisticsPeriod = :period')
            ->andWhere('ls.statisticsDate >= :startDate')
            ->andWhere('ls.statisticsDate <= :endDate')
            ->setParameter('type', $type)
            ->setParameter('period', $period)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ls.statisticsDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取汇总统计
     */
    public function getSummaryStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('ls')
            ->select('
                COUNT(ls.id) as totalRecords,
                SUM(ls.totalUsers) as totalUsers,
                SUM(ls.activeUsers) as activeUsers,
                SUM(ls.totalSessions) as totalSessions,
                SUM(ls.totalDuration) as totalDuration,
                SUM(ls.effectiveDuration) as effectiveDuration,
                SUM(ls.anomalyCount) as totalAnomalies,
                AVG(ls.completionRate) as avgCompletionRate,
                AVG(ls.averageEfficiency) as avgEfficiency,
                MAX(ls.totalUsers) as peakUsers,
                MAX(ls.totalSessions) as peakSessions
            ')
            ->andWhere('ls.statisticsDate >= :startDate')
            ->andWhere('ls.statisticsDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * 查找需要更新的统计记录
     */
    public function findNeedingUpdate(StatisticsPeriod $period): array
    {
        $cutoffTime = new \DateTimeImmutable();
        
        // 根据周期确定更新阈值
        switch ($period) {
            case StatisticsPeriod::REAL_TIME:
                $cutoffTime->sub(new \DateInterval('PT5M')); // 5分钟前
                break;
            case StatisticsPeriod::HOURLY:
                $cutoffTime->sub(new \DateInterval('PT1H')); // 1小时前
                break;
            case StatisticsPeriod::DAILY:
                $cutoffTime->sub(new \DateInterval('P1D')); // 1天前
                break;
            default:
                $cutoffTime->sub(new \DateInterval('P1D')); // 默认1天前
        }

        return $this->createQueryBuilder('ls')
            ->andWhere('ls.statisticsPeriod = :period')
            ->andWhere('ls.updateTime < :cutoffTime OR ls.updateTime IS NULL')
            ->setParameter('period', $period)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('ls.updateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 删除过期的统计记录
     */
    public function deleteExpiredRecords(\DateTimeInterface $beforeDate): int
    {
        return $this->createQueryBuilder('ls')
            ->delete()
            ->andWhere('ls.statisticsDate < :beforeDate')
            ->setParameter('beforeDate', $beforeDate)
            ->getQuery()
            ->execute();
    }

    /**
     * 按类型统计记录数量
     */
    public function countByType(): array
    {
        return $this->createQueryBuilder('ls')
            ->select('ls.statisticsType, COUNT(ls.id) as count')
            ->groupBy('ls.statisticsType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按周期统计记录数量
     */
    public function countByPeriod(): array
    {
        return $this->createQueryBuilder('ls')
            ->select('ls.statisticsPeriod, COUNT(ls.id) as count')
            ->groupBy('ls.statisticsPeriod')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取最活跃的统计类型
     */
    public function getMostActiveTypes(int $limit = 10): array
    {
        return $this->createQueryBuilder('ls')
            ->select('ls.statisticsType, SUM(ls.totalSessions) as totalSessions')
            ->groupBy('ls.statisticsType')
            ->orderBy('totalSessions', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 