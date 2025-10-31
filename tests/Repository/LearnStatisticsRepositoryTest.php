<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Repository\LearnStatisticsRepository;

/**
 * LearnStatisticsRepository 集成测试
 *
 * @template TEntity of LearnStatistics
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnStatisticsRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnStatisticsRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnStatisticsRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnStatistics::class);
        $this->assertInstanceOf(LearnStatisticsRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 为需要数据隔离的测试清理数据
     */
    private function clearDataForTestIsolation(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM ' . LearnStatistics::class)->execute();
        self::getEntityManager()->flush();
    }

    /**
     * 创建测试统计记录
     * 为了避免唯一约束冲突，使用计数器生成唯一的日期
     */
    private static int $dateCounter = 0;

    private static int $uniqueCounter = 0;

    private function createTestStatistics(
        StatisticsType $type = StatisticsType::USER,
        StatisticsPeriod $period = StatisticsPeriod::DAILY,
        ?\DateTimeInterface $statisticsDate = null,
        int $totalUsers = 100,
        int $activeUsers = 80,
        int $totalSessions = 200,
        float $totalDuration = 360000,
        float $effectiveDuration = 300000,
        int $anomalyCount = 5,
        float $completionRate = 85.5,
        float $averageEfficiency = 0.83,
    ): LearnStatistics {
        $statistics = new LearnStatistics();
        $statistics->setStatisticsType($type);
        $statistics->setStatisticsPeriod($period);

        // 生成唯一日期，避免约束冲突
        if (null === $statisticsDate) {
            $date = new \DateTimeImmutable('+' . (++self::$dateCounter) . ' days');
        } else {
            $date = $statisticsDate instanceof \DateTimeImmutable
                ? $statisticsDate
                : \DateTimeImmutable::createFromInterface($statisticsDate);
        }
        $statistics->setStatisticsDate($date);
        $statistics->setTotalUsers($totalUsers);
        $statistics->setActiveUsers($activeUsers);
        $statistics->setTotalSessions($totalSessions);
        $statistics->setTotalDuration($totalDuration);
        $statistics->setEffectiveDuration($effectiveDuration);
        $statistics->setAnomalyCount($anomalyCount);
        $statistics->setCompletionRate($completionRate);
        $statistics->setAverageEfficiency($averageEfficiency);
        $statistics->setExtendedData(['version' => '1.0']);
        $statistics->setUpdateTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($statistics);
        self::getEntityManager()->flush();

        return $statistics;
    }

    /**
     * 测试按类型和周期查找统计
     */
    public function testFindByTypeAndPeriodReturnsCorrectRecords(): void
    {
        $this->clearDataForTestIsolation();

        // 创建2条USER+DAILY记录，使用不同日期避免unique约束冲突
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-01'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-02'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY);

        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertCount(2, $results);
        foreach ($results as $stat) {
            $this->assertInstanceOf(LearnStatistics::class, $stat);
            $this->assertEquals(StatisticsType::USER, $stat->getStatisticsType());
            $this->assertEquals(StatisticsPeriod::DAILY, $stat->getStatisticsPeriod());
        }
    }

    /**
     * 测试按日期范围查找统计
     */
    public function testFindByDateRangeReturnsRecordsInRange(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        // 使用不同类型+周期避免unique约束冲突
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'));
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-20'));
        $this->createTestStatistics(StatisticsType::SYSTEM, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findByDateRange($startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $stat) {
            $this->assertInstanceOf(LearnStatistics::class, $stat);
            $this->assertGreaterThanOrEqual($startDate, $stat->getStatisticsDate());
            $this->assertLessThanOrEqual($endDate, $stat->getStatisticsDate());
        }
    }

    /**
     * 测试查找最新的统计记录
     */
    public function testFindLatestByTypeReturnsNewestRecord(): void
    {
        // 使用不同日期创建3条USER+DAILY记录，避免unique约束冲突
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-10'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-20'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-15'));

        $result = $this->repository->findLatestByType(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertNotNull($result);
        $this->assertEquals('2024-02-20', $result->getStatisticsDate()->format('Y-m-d'));
    }

    /**
     * 测试查找不存在的最新记录
     */
    public function testFindLatestByTypeWithNoRecordsReturnsNull(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);

        $result = $this->repository->findLatestByType(StatisticsType::COURSE, StatisticsPeriod::HOURLY);

        $this->assertNull($result);
    }

    /**
     * 测试按日期查找统计
     */
    public function testFindByDateReturnsAllTypesForDate(): void
    {
        // 使用与现有data fixtures不同的日期
        $testDate = new \DateTimeImmutable('2099-01-01');

        // 手动创建实体，避免使用可能有问题的helper方法
        $entity1 = new LearnStatistics();
        $entity1->setStatisticsType(StatisticsType::USER);
        $entity1->setStatisticsPeriod(StatisticsPeriod::DAILY);
        $entity1->setStatisticsDate($testDate);
        $entity1->setTotalUsers(100);
        $entity1->setActiveUsers(80);
        $entity1->setTotalSessions(200);
        $entity1->setTotalDuration(360000);
        $entity1->setEffectiveDuration(300000);
        $entity1->setAnomalyCount(5);
        $entity1->setCompletionRate(85.5);
        $entity1->setAverageEfficiency(0.83);
        $entity1->setExtendedData(['test' => 'data']);

        $entity2 = new LearnStatistics();
        $entity2->setStatisticsType(StatisticsType::COURSE);
        $entity2->setStatisticsPeriod(StatisticsPeriod::HOURLY);
        $entity2->setStatisticsDate($testDate);
        $entity2->setTotalUsers(50);
        $entity2->setActiveUsers(40);
        $entity2->setTotalSessions(100);
        $entity2->setTotalDuration(180000);
        $entity2->setEffectiveDuration(150000);
        $entity2->setAnomalyCount(2);
        $entity2->setCompletionRate(90.0);
        $entity2->setAverageEfficiency(0.85);
        $entity2->setExtendedData(['test' => 'data']);

        $entity3 = new LearnStatistics();
        $entity3->setStatisticsType(StatisticsType::SYSTEM);
        $entity3->setStatisticsPeriod(StatisticsPeriod::MONTHLY);
        $entity3->setStatisticsDate($testDate);
        $entity3->setTotalUsers(200);
        $entity3->setActiveUsers(160);
        $entity3->setTotalSessions(400);
        $entity3->setTotalDuration(720000);
        $entity3->setEffectiveDuration(600000);
        $entity3->setAnomalyCount(10);
        $entity3->setCompletionRate(80.0);
        $entity3->setAverageEfficiency(0.80);
        $entity3->setExtendedData(['test' => 'data']);

        // 保存到数据库
        $em = self::getEntityManager();
        $em->persist($entity1);
        $em->persist($entity2);
        $em->persist($entity3);
        $em->flush();

        // 执行查询
        $results = $this->repository->findByDate($testDate);

        // 验证结果
        $this->assertCount(3, $results, '应该找到3条记录：USER、COURSE、SYSTEM各一条');

        $foundTypes = [];
        foreach ($results as $stat) {
            $this->assertInstanceOf(LearnStatistics::class, $stat);
            $this->assertEquals($testDate->format('Y-m-d'), $stat->getStatisticsDate()->format('Y-m-d'));
            $foundTypes[] = $stat->getStatisticsType();
        }

        // 验证找到了所有3种类型
        $this->assertContains(StatisticsType::USER, $foundTypes);
        $this->assertContains(StatisticsType::COURSE, $foundTypes);
        $this->assertContains(StatisticsType::SYSTEM, $foundTypes);
    }

    /**
     * 测试获取统计概览
     */
    public function testGetStatisticsOverviewReturnsAggregatedData(): void
    {
        // 使用不同的日期避免与其他测试冲突
        $date = new \DateTimeImmutable('2024-03-15');

        // 创建不同类型+周期的记录，避免unique约束冲突
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, $date, 100, 80, 200);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY, $date, 50, 40, 100);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, $date, 200, 150, 300);

        $overview = $this->repository->getStatisticsOverview($date);

        $this->assertCount(3, $overview);

        // 验证聚合数据
        /** @var array<string, mixed>|null $userDailyOverview */
        $userDailyOverview = null;
        foreach ($overview as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('statisticsType', $item);
            $this->assertArrayHasKey('statisticsPeriod', $item);
            if (StatisticsType::USER === $item['statisticsType'] && StatisticsPeriod::DAILY === $item['statisticsPeriod']) {
                $userDailyOverview = $item;
                break;
            }
        }

        $this->assertIsArray($userDailyOverview);
        $this->assertArrayHasKey('totalUsers', $userDailyOverview);
        $this->assertArrayHasKey('activeUsers', $userDailyOverview);
        $this->assertArrayHasKey('totalSessions', $userDailyOverview);
        $this->assertEquals(100, $userDailyOverview['totalUsers']);
        $this->assertEquals(80, $userDailyOverview['activeUsers']);
        $this->assertEquals(200, $userDailyOverview['totalSessions']);
    }

    /**
     * 测试获取趋势数据
     */
    public function testGetTrendDataReturnsTimeSeriesData(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-10'), 100);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'), 120);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-20'), 150);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'), 200);

        $trends = $this->repository->getTrendData(StatisticsType::USER, StatisticsPeriod::DAILY, $startDate, $endDate);

        $this->assertCount(3, $trends);

        // 验证按日期升序排列
        $previousDate = null;
        foreach ($trends as $trend) {
            $this->assertIsArray($trend);
            $this->assertArrayHasKey('statisticsDate', $trend);
            if (null !== $previousDate) {
                $this->assertGreaterThan($previousDate, $trend['statisticsDate']);
            }
            $previousDate = $trend['statisticsDate'];
        }
    }

    /**
     * 测试获取汇总统计
     */
    public function testGetSummaryStatsReturnsAggregatedSummary(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-10'), 100, 80, 200, 360000, 300000, 5, 85.0, 0.83);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'), 150, 120, 300, 540000, 450000, 8, 90.0, 0.83);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-01'), 200, 160, 400, 720000, 600000, 10, 95.0, 0.83);

        $summary = $this->repository->getSummaryStats($startDate, $endDate);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('totalRecords', $summary);
        $this->assertArrayHasKey('totalUsers', $summary);
        $this->assertArrayHasKey('activeUsers', $summary);
        $this->assertArrayHasKey('totalSessions', $summary);
        $this->assertArrayHasKey('peakUsers', $summary);
        $this->assertArrayHasKey('peakSessions', $summary);
        $this->assertArrayHasKey('avgCompletionRate', $summary);

        $this->assertEquals(2, $summary['totalRecords']);
        $this->assertEquals(250, $summary['totalUsers']); // 100 + 150
        $this->assertEquals(200, $summary['activeUsers']); // 80 + 120
        $this->assertEquals(500, $summary['totalSessions']); // 200 + 300
        $this->assertEquals(150, $summary['peakUsers']); // max(100, 150)
        $this->assertEquals(300, $summary['peakSessions']); // max(200, 300)
        $this->assertEqualsWithDelta(87.5, $summary['avgCompletionRate'], 0.01); // (85 + 90) / 2
    }

    /**
     * 测试删除过期记录
     */
    public function testDeleteExpiredRecordsRemovesOldRecords(): void
    {
        // 清理现有数据确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . LearnStatistics::class)->execute();
        self::getEntityManager()->flush();

        // 创建测试数据
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2023-01-01'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2023-06-01'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-03'));

        // 确保数据持久化
        self::getEntityManager()->flush();

        // 验证创建了3条记录
        $totalBefore = $this->repository->createQueryBuilder('ls')->select('COUNT(ls.id)')->getQuery()->getSingleScalarResult();
        $this->assertEquals(3, $totalBefore, '应该有3条测试记录');

        $deletedCount = $this->repository->deleteExpiredRecords(new \DateTimeImmutable('2024-01-02'));

        $this->assertEquals(2, $deletedCount);

        // 验证只剩下一条记录
        $remaining = $this->repository->findAll();
        $this->assertCount(1, $remaining);
        $this->assertEquals('2024-01-03', $remaining[0]->getStatisticsDate()->format('Y-m-d'));
    }

    /**
     * 测试按类型统计记录数量
     */
    public function testCountByTypeReturnsTypeCounts(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER);
        $this->createTestStatistics(StatisticsType::USER);
        $this->createTestStatistics(StatisticsType::COURSE);
        $this->createTestStatistics(StatisticsType::COURSE);
        $this->createTestStatistics(StatisticsType::COURSE);
        $this->createTestStatistics(StatisticsType::SYSTEM);

        $counts = $this->repository->countByType();

        $this->assertCount(3, $counts);

        $typeCounts = [];
        foreach ($counts as $count) {
            $this->assertIsArray($count);
            $this->assertArrayHasKey('statisticsType', $count);
            $this->assertArrayHasKey('count', $count);
            if (isset($count['statisticsType']) && is_object($count['statisticsType']) && property_exists($count['statisticsType'], 'value')) {
                $typeCounts[$count['statisticsType']->value] = $count['count'];
            }
        }

        $this->assertEquals(3, $typeCounts[StatisticsType::COURSE->value]);
        $this->assertEquals(2, $typeCounts[StatisticsType::USER->value]);
        $this->assertEquals(1, $typeCounts[StatisticsType::SYSTEM->value]);
    }

    /**
     * 测试按周期统计记录数量
     */
    public function testCountByPeriodReturnsPeriodCounts(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::MONTHLY);

        $counts = $this->repository->countByPeriod();

        $this->assertCount(3, $counts);

        $periodCounts = [];
        foreach ($counts as $count) {
            $this->assertIsArray($count);
            $this->assertArrayHasKey('statisticsPeriod', $count);
            $this->assertArrayHasKey('count', $count);
            if (isset($count['statisticsPeriod']) && is_object($count['statisticsPeriod']) && property_exists($count['statisticsPeriod'], 'value')) {
                $periodCounts[$count['statisticsPeriod']->value] = $count['count'];
            }
        }

        $this->assertEquals(3, $periodCounts[StatisticsPeriod::DAILY->value]);
        $this->assertEquals(2, $periodCounts[StatisticsPeriod::HOURLY->value]);
        $this->assertEquals(1, $periodCounts[StatisticsPeriod::MONTHLY->value]);
    }

    /**
     * 测试获取最活跃的统计类型
     */
    public function testGetMostActiveTypesReturnsSortedByActivity(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100, 80, 500);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, null, 200, 150, 1000);
        $this->createTestStatistics(StatisticsType::SYSTEM, StatisticsPeriod::DAILY, null, 50, 40, 200);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, null, 150, 120, 800);

        $activeTypes = $this->repository->getMostActiveTypes(2);

        $this->assertCount(2, $activeTypes);

        $this->assertIsArray($activeTypes[0]);
        $this->assertArrayHasKey('statisticsType', $activeTypes[0]);
        $this->assertArrayHasKey('totalSessions', $activeTypes[0]);
        $this->assertEquals(StatisticsType::COURSE, $activeTypes[0]['statisticsType']);
        $this->assertEquals(1800, $activeTypes[0]['totalSessions']); // 1000 + 800

        $this->assertIsArray($activeTypes[1]);
        $this->assertArrayHasKey('statisticsType', $activeTypes[1]);
        $this->assertArrayHasKey('totalSessions', $activeTypes[1]);
        $this->assertEquals(StatisticsType::USER, $activeTypes[1]['statisticsType']);
        $this->assertEquals(500, $activeTypes[1]['totalSessions']);
    }

    /**
     * 测试空数据场景
     */
    public function testFindByTypeAndPeriodWithNoDataReturnsEmptyArray(): void
    {
        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertEmpty($results);
    }

    /**
     * 测试带限制的查询
     */
    public function testFindByTypeAndPeriodWithLimitReturnsLimitedResults(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->createTestStatistics(
                StatisticsType::USER,
                StatisticsPeriod::DAILY,
                new \DateTimeImmutable("2024-01-{$i}")
            );
        }

        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY, 5);

        $this->assertCount(5, $results);
    }

    /**
     * 测试查找需要更新的统计记录
     */
    public function testFindNeedingUpdate(): void
    {
        $this->clearDataForTestIsolation();

        $result = $this->repository->findNeedingUpdate(StatisticsPeriod::DAILY);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找需要更新的统计记录（实时）
     */
    public function testFindNeedingUpdateRealTime(): void
    {
        $this->clearDataForTestIsolation();

        $result = $this->repository->findNeedingUpdate(StatisticsPeriod::REAL_TIME);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找需要更新的统计记录（小时）
     */
    public function testFindNeedingUpdateHourly(): void
    {
        $this->clearDataForTestIsolation();

        $result = $this->repository->findNeedingUpdate(StatisticsPeriod::HOURLY);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找需要更新的统计记录（月度）
     */
    public function testFindNeedingUpdateMonthly(): void
    {
        $this->clearDataForTestIsolation();

        $result = $this->repository->findNeedingUpdate(StatisticsPeriod::MONTHLY);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试 find 方法
     */
    public function testFind(): void
    {
        $statistics = $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);

        $found = $this->repository->find($statistics->getId());

        $this->assertNotNull($found);
        $this->assertEquals($statistics->getId(), $found->getId());
        $this->assertEquals(StatisticsType::USER, $found->getStatisticsType());
    }

    /**
     * 测试 find 方法查找不存在的记录
     */
    public function testFindNonExistent(): void
    {
        $found = $this->repository->find(999999);

        $this->assertNull($found);
    }

    /**
     * 测试 findAll 方法
     */
    public function testFindAll(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::HOURLY);

        $results = $this->repository->findAll();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(LearnStatistics::class, $results);
    }

    /**
     * 测试 findBy 方法
     */
    public function testFindBy(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY, null, 200);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, null, 150);

        $results = $this->repository->findBy(['statisticsType' => StatisticsType::USER]);

        $this->assertCount(2, $results);
        foreach ($results as $stat) {
            $this->assertEquals(StatisticsType::USER, $stat->getStatisticsType());
        }
    }

    /**
     * 测试 findBy 方法带排序
     */

    /**
     * 测试 findBy 方法带分页
     */
    public function testFindByWithLimitAndOffset(): void
    {
        $this->clearDataForTestIsolation();

        for ($i = 1; $i <= 5; ++$i) {
            $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        }

        $results = $this->repository->findBy(['statisticsType' => StatisticsType::USER], null, 2, 1);

        $this->assertCount(2, $results);
    }

    /**
     * 测试 findOneBy 方法
     */
    public function testFindOneBy(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::HOURLY, null, 200);

        $result = $this->repository->findOneBy(['statisticsType' => StatisticsType::USER]);

        $this->assertNotNull($result);
        $this->assertEquals(StatisticsType::USER, $result->getStatisticsType());
        $this->assertEquals(100, $result->getTotalUsers());
    }

    /**
     * 测试 findOneBy 方法查找不存在的记录
     */
    public function testFindOneByNonExistent(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);

        $result = $this->repository->findOneBy(['statisticsType' => StatisticsType::SYSTEM]);

        $this->assertNull($result);
    }

    /**
     * 测试 save 方法
     */
    public function testSave(): void
    {
        $statistics = new LearnStatistics();
        $statistics->setStatisticsType(StatisticsType::USER);
        $statistics->setStatisticsPeriod(StatisticsPeriod::DAILY);
        $statistics->setStatisticsDate(new \DateTimeImmutable());
        $statistics->setTotalUsers(100);
        $statistics->setActiveUsers(80);
        $statistics->setTotalSessions(200);
        $statistics->setTotalDuration(360000);
        $statistics->setEffectiveDuration(300000);
        $statistics->setAnomalyCount(5);
        $statistics->setCompletionRate(85.5);
        $statistics->setAverageEfficiency(0.83);
        $statistics->setExtendedData(['version' => '1.0']);
        $statistics->setUpdateTime(new \DateTimeImmutable());

        $this->repository->save($statistics, true);

        $this->assertNotNull($statistics->getId());

        $found = $this->repository->find($statistics->getId());
        $this->assertNotNull($found);
        $this->assertEquals(StatisticsType::USER, $found->getStatisticsType());
        $this->assertEquals(100, $found->getTotalUsers());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemove(): void
    {
        $statistics = $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $id = $statistics->getId();

        $this->repository->remove($statistics, true);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    /**
     * 测试查询枚举和可空字段
     */
    public function testFindByEnumAndNullableFields(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100, 80);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::HOURLY, null, 200, 150);

        $userResults = $this->repository->findBy(['statisticsType' => StatisticsType::USER]);
        $this->assertCount(1, $userResults);
        $this->assertEquals(StatisticsType::USER, $userResults[0]->getStatisticsType());

        $dailyResults = $this->repository->findBy(['statisticsPeriod' => StatisticsPeriod::DAILY]);
        $this->assertCount(1, $dailyResults);
        $this->assertEquals(StatisticsPeriod::DAILY, $dailyResults[0]->getStatisticsPeriod());

        $combinedResults = $this->repository->findBy([
            'statisticsType' => StatisticsType::USER,
            'statisticsPeriod' => StatisticsPeriod::DAILY,
        ]);
        $this->assertCount(1, $combinedResults);
    }

    /**
     * 测试查找一个存在的实体
     */

    /**
     * 测试存在记录时 findAll 应返回实体数组
     */

    /**
     * 测试 findBy 匹配条件应返回实体数组
     */

    /**
     * 测试 findBy 应遵循 OrderBy 子句
     */

    /**
     * 测试 findBy 应遵循限制和偏移参数
     */

    /**
     * 测试 findOneBy 匹配条件应返回实体
     */

    /**
     * 测试 findOneBy 排序逻辑
     */
    public function testFindOneByWithOrderBy(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 200);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 150);

        $result = $this->repository->findOneBy(['statisticsType' => StatisticsType::USER], ['totalUsers' => 'DESC']);

        $this->assertNotNull($result);
        $this->assertEquals(200, $result->getTotalUsers());
    }

    /**
     * 测试 IS NULL 查询可空字段
     */
    public function testFindByNullableFieldsIsNull(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::HOURLY);

        // extendedData 默认不为 null，因此查找为 null 的应该为空
        $results = $this->repository->findBy(['extendedData' => null]);
        $this->assertEmpty($results);
    }

    /**
     * 测试 count IS NULL 查询可空字段
     */
    public function testCountWithNullableFieldsISNull(): void
    {
        $this->clearDataForTestIsolation();

        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::HOURLY);

        // extendedData 默认不为 null，因此计数应该为 0
        $count = $this->repository->count(['extendedData' => null]);
        $this->assertEquals(0, $count);
    }

    protected function createNewEntity(): object
    {
        $entity = new LearnStatistics();

        // 设置基本字段以满足数据库约束
        // 每次生成唯一的组合避免unique约束冲突
        $types = [StatisticsType::USER, StatisticsType::COURSE, StatisticsType::SYSTEM];
        $periods = [StatisticsPeriod::DAILY, StatisticsPeriod::HOURLY, StatisticsPeriod::MONTHLY];

        $entity->setStatisticsType($types[self::$uniqueCounter % count($types)]);
        $entity->setStatisticsPeriod($periods[self::$uniqueCounter % count($periods)]);
        $entity->setStatisticsDate(new \DateTimeImmutable('+' . (++self::$uniqueCounter) . ' days'));
        $entity->setTotalUsers(100);
        $entity->setActiveUsers(80);
        $entity->setTotalSessions(200);
        $entity->setTotalDuration(360000);
        $entity->setEffectiveDuration(300000);
        $entity->setAnomalyCount(5);
        $entity->setCompletionRate(85.5);
        $entity->setAverageEfficiency(0.83);
        $entity->setExtendedData(['version' => '1.0']);
        $entity->setUpdateTime(new \DateTimeImmutable());

        return $entity;
    }

    /** @return ServiceEntityRepository<LearnStatistics> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
