<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * @extends ServiceEntityRepository<LearnAnomaly>
 */
#[AsRepository(entityClass: LearnAnomaly::class)]
class LearnAnomalyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnAnomaly::class);
    }

    /**
     * 查找会话的异常记录
     *
     * @return array<LearnAnomaly>
     */
    public function findBySession(string $sessionId): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('la.detectTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找未处理的异常
     *
     * @return array<LearnAnomaly>
     */
    public function findUnprocessed(): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.status = :detected OR la.status = :investigating')
            ->setParameter('detected', AnomalyStatus::DETECTED)
            ->setParameter('investigating', AnomalyStatus::INVESTIGATING)
            ->orderBy('la.severity', 'DESC')
            ->addOrderBy('la.detectTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找高优先级异常
     *
     * @return array<LearnAnomaly>
     */
    public function findHighPriority(): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.severity = :high OR la.severity = :critical')
            ->andWhere('la.status != :resolved AND la.status != :ignored')
            ->setParameter('high', AnomalySeverity::HIGH)
            ->setParameter('critical', AnomalySeverity::CRITICAL)
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->setParameter('ignored', AnomalyStatus::IGNORED)
            ->orderBy('la.severity', 'DESC')
            ->addOrderBy('la.detectTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按类型统计异常
     *
     * @return array<array{anomalyType: mixed, count: int}>
     */
    public function getAnomalyStatsByType(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<array{anomalyType: mixed, count: int}> */
        return $this->createQueryBuilder('la')
            ->select('la.anomalyType, COUNT(la.id) as count')
            ->andWhere('la.detectTime >= :startDate')
            ->andWhere('la.detectTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('la.anomalyType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按严重程度统计异常
     *
     * @return array<array{severity: mixed, count: int}>
     */
    public function getAnomalyStatsBySeverity(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<array{severity: mixed, count: int}> */
        return $this->createQueryBuilder('la')
            ->select('la.severity, COUNT(la.id) as count')
            ->andWhere('la.detectTime >= :startDate')
            ->andWhere('la.detectTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('la.severity')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找自动检测的异常
     *
     * @return array<LearnAnomaly>
     */
    public function findAutoDetected(int $limit = 100): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.isAutoDetected = true')
            ->orderBy('la.detectTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找特定类型的异常
     *
     * @return array<LearnAnomaly>
     */
    public function findByType(AnomalyType $type, int $limit = 50): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.anomalyType = :type')
            ->setParameter('type', $type)
            ->orderBy('la.detectTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找处理时间过长的异常
     *
     * @return array<LearnAnomaly>
     */
    public function findLongProcessing(\DateTimeInterface $threshold): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.detectTime < :threshold')
            ->andWhere('la.status = :detected OR la.status = :investigating')
            ->setParameter('threshold', $threshold)
            ->setParameter('detected', AnomalyStatus::DETECTED)
            ->setParameter('investigating', AnomalyStatus::INVESTIGATING)
            ->orderBy('la.detectTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计处理效率
     *
     * @return array{totalAnomalies: int, resolvedCount: int, ignoredCount: int, avgProcessingTime: float|null}
     */
    public function getProcessingEfficiencyStats(): array
    {
        $baseStats = $this->getBaseStats();
        $avgProcessingTime = $this->calculateAverageProcessingTime();

        return [
            'totalAnomalies' => $this->extractIntValue($baseStats, 'totalAnomalies'),
            'resolvedCount' => $this->extractIntValue($baseStats, 'resolvedCount'),
            'ignoredCount' => $this->extractIntValue($baseStats, 'ignoredCount'),
            'avgProcessingTime' => $avgProcessingTime,
        ];
    }

    /**
     * 获取基础统计数据
     *
     * @return array{totalAnomalies: string|int, resolvedCount: string|int|null, ignoredCount: string|int|null}
     */
    private function getBaseStats(): array
    {
        /** @var array{totalAnomalies: string|int, resolvedCount: string|int|null, ignoredCount: string|int|null} */
        return $this->createQueryBuilder('la')
            ->select('
                COUNT(la.id) as totalAnomalies,
                SUM(CASE WHEN la.status = :resolved THEN 1 ELSE 0 END) as resolvedCount,
                SUM(CASE WHEN la.status = :ignored THEN 1 ELSE 0 END) as ignoredCount
            ')
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->setParameter('ignored', AnomalyStatus::IGNORED)
            ->getQuery()
            ->getSingleResult()
        ;
    }

    /**
     * 计算平均处理时间
     */
    private function calculateAverageProcessingTime(): ?float
    {
        $resolvedAnomalies = $this->getResolvedAnomalies();

        if ([] === $resolvedAnomalies) {
            return null;
        }

        return $this->computeAverageTime($resolvedAnomalies);
    }

    /**
     * 获取已解决的异常数据
     *
     * @return array<array{detectTime: \DateTimeInterface|null, resolveTime: \DateTimeInterface|null}>
     */
    private function getResolvedAnomalies(): array
    {
        /** @var array<array{detectTime: \DateTimeInterface|null, resolveTime: \DateTimeInterface|null}> */
        return $this->createQueryBuilder('la')
            ->select('la.detectTime, la.resolveTime')
            ->where('la.status = :resolved')
            ->andWhere('la.resolveTime IS NOT NULL')
            ->andWhere('la.detectTime IS NOT NULL')
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 计算平均时间
     *
     * @param array<array{detectTime: \DateTimeInterface|null, resolveTime: \DateTimeInterface|null}> $resolvedAnomalies
     */
    private function computeAverageTime(array $resolvedAnomalies): ?float
    {
        $totalSeconds = 0;
        $validCount = 0;

        foreach ($resolvedAnomalies as $anomaly) {
            $timeDiff = $this->calculateTimeDifference($anomaly);
            if (null !== $timeDiff) {
                $totalSeconds += $timeDiff;
                ++$validCount;
            }
        }

        return $validCount > 0 ? $totalSeconds / $validCount : null;
    }

    /**
     * 计算时间差
     *
     * @param array{detectTime: mixed, resolveTime: mixed} $anomaly
     */
    private function calculateTimeDifference(array $anomaly): ?int
    {
        $detectTime = $anomaly['detectTime'] ?? null;
        $resolveTime = $anomaly['resolveTime'] ?? null;

        if ($detectTime instanceof \DateTimeInterface && $resolveTime instanceof \DateTimeInterface) {
            return $resolveTime->getTimestamp() - $detectTime->getTimestamp();
        }

        return null;
    }

    /**
     * 安全提取整数值
     *
     * @param array<string, mixed> $data
     */
    private function extractIntValue(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 查找最近的异常趋势
     *
     * @return array<array{date: string, count: int}>
     */
    public function getRecentTrends(int $days = 7): array
    {
        $startDate = new \DateTimeImmutable("-{$days} days");

        /** @var array<array{date: string, count: int}> */
        return $this->createQueryBuilder('la')
            ->select('DATE(la.detectTime) as date, COUNT(la.id) as count')
            ->andWhere('la.detectTime >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('DATE(la.detectTime)')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据日期范围查找异常
     *
     * @return array<LearnAnomaly>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.detectTime >= :startDate')
            ->andWhere('la.detectTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('la.detectTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找未解决的异常
     *
     * @return array<LearnAnomaly>
     */
    public function findUnresolved(): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->andWhere('la.status != :resolved AND la.status != :ignored')
            ->setParameter('resolved', AnomalyStatus::RESOLVED)
            ->setParameter('ignored', AnomalyStatus::IGNORED)
            ->orderBy('la.severity', 'DESC')
            ->addOrderBy('la.detectTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据用户ID和课程ID查找异常记录
     *
     * @return array<LearnAnomaly>
     */
    public function findByUserAndCourse(string $userId, string $courseId): array
    {
        /** @var array<LearnAnomaly> */
        return $this->createQueryBuilder('la')
            ->leftJoin('la.session', 's')
            ->leftJoin('s.registration', 'r')
            ->leftJoin('s.lesson', 'l')
            ->leftJoin('l.chapter', 'ch')
            ->leftJoin('ch.course', 'c')
            ->where('r.student = :userId')
            ->andWhere('c.id = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('la.detectTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按日期范围和过滤条件查找异常
     *
     * @param array<string, mixed> $filters
     * @return array<LearnAnomaly>
     */
    public function findByDateRangeAndFilters(\DateTimeInterface $startDate, \DateTimeInterface $endDate, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('la')
            ->where('la.detectTime >= :startDate')
            ->andWhere('la.detectTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
        ;

        if (isset($filters['courseId'])) {
            $qb->leftJoin('la.session', 's')
                ->leftJoin('s.lesson', 'l')
                ->leftJoin('l.chapter', 'ch')
                ->leftJoin('ch.course', 'c')
                ->andWhere('c.id = :courseId')
                ->setParameter('courseId', $filters['courseId'])
            ;
        }

        if (isset($filters['userId'])) {
            $qb->leftJoin('la.session', 's2')
                ->leftJoin('s2.registration', 'r')
                ->andWhere('r.student = :userId')
                ->setParameter('userId', $filters['userId'])
            ;
        }

        /** @var array<LearnAnomaly> */
        return $qb->orderBy('la.detectTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(LearnAnomaly $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(LearnAnomaly $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
