<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserAgentBundle\DoctrineUserAgentBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Repository\LearnStatisticsRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnStatisticsRepository 集成测试
 */
class LearnStatisticsRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnStatisticsRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineUserAgentBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Core bundles
            TrainRecordBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        $repository = $this->entityManager->getRepository(LearnStatistics::class);
        $this->assertInstanceOf(LearnStatisticsRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnStatistics::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试统计记录
     */
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
        float $averageEfficiency = 0.83
    ): LearnStatistics {
        $statistics = new LearnStatistics();
        $statistics->setStatisticsType($type);
        $statistics->setStatisticsPeriod($period);
        $statistics->setStatisticsDate($statisticsDate ?? new \DateTimeImmutable());
        $statistics->setTotalUsers($totalUsers);
        $statistics->setActiveUsers($activeUsers);
        $statistics->setTotalSessions($totalSessions);
        $statistics->setTotalDuration($totalDuration);
        $statistics->setEffectiveDuration($effectiveDuration);
        $statistics->setAnomalyCount($anomalyCount);
        $statistics->setCompletionRate($completionRate);
        $statistics->setAverageEfficiency($averageEfficiency);
        $statistics->setMetadata(json_encode(['version' => '1.0']));
        $statistics->setUpdateTime(new \DateTimeImmutable());

        $this->entityManager->persist($statistics);
        $this->entityManager->flush();

        return $statistics;
    }

    /**
     * 测试按类型和周期查找统计
     */
    public function test_findByTypeAndPeriod_returnsCorrectRecords(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY);

        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertCount(2, $results);
        foreach ($results as $stat) {
            $this->assertEquals(StatisticsType::USER, $stat->getStatisticsType());
            $this->assertEquals(StatisticsPeriod::DAILY, $stat->getStatisticsPeriod());
        }
    }

    /**
     * 测试按日期范围查找统计
     */
    public function test_findByDateRange_returnsRecordsInRange(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'));
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-20'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findByDateRange($startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $stat) {
            $this->assertGreaterThanOrEqual($startDate, $stat->getStatisticsDate());
            $this->assertLessThanOrEqual($endDate, $stat->getStatisticsDate());
        }
    }

    /**
     * 测试查找最新的统计记录
     */
    public function test_findLatestByType_returnsNewestRecord(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-10'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-20'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'));

        $result = $this->repository->findLatestByType(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertNotNull($result);
        $this->assertEquals('2024-01-20', $result->getStatisticsDate()->format('Y-m-d'));
    }

    /**
     * 测试查找不存在的最新记录
     */
    public function test_findLatestByType_withNoRecords_returnsNull(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY);

        $result = $this->repository->findLatestByType(StatisticsType::COURSE, StatisticsPeriod::HOURLY);

        $this->assertNull($result);
    }

    /**
     * 测试按日期查找统计
     */
    public function test_findByDate_returnsAllTypesForDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, $date);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, $date);
        $this->createTestStatistics(StatisticsType::SYSTEM, StatisticsPeriod::DAILY, $date);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-16'));

        $results = $this->repository->findByDate($date);

        $this->assertCount(3, $results);
        foreach ($results as $stat) {
            $this->assertEquals($date->format('Y-m-d'), $stat->getStatisticsDate()->format('Y-m-d'));
        }
    }

    /**
     * 测试获取统计概览
     */
    public function test_getStatisticsOverview_returnsAggregatedData(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, $date, 100, 80, 200);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::HOURLY, $date, 50, 40, 100);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, $date, 200, 150, 300);

        $overview = $this->repository->getStatisticsOverview($date);

        $this->assertCount(3, $overview);
        
        // 验证聚合数据
        $userDailyOverview = null;
        foreach ($overview as $item) {
            if ($item['statisticsType'] === StatisticsType::USER && $item['statisticsPeriod'] === StatisticsPeriod::DAILY) {
                $userDailyOverview = $item;
                break;
            }
        }
        
        $this->assertNotNull($userDailyOverview);
        $this->assertEquals(100, $userDailyOverview['totalUsers']);
        $this->assertEquals(80, $userDailyOverview['activeUsers']);
        $this->assertEquals(200, $userDailyOverview['totalSessions']);
    }

    /**
     * 测试获取趋势数据
     */
    public function test_getTrendData_returnsTimeSeriesData(): void
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
            if ($previousDate !== null) {
                $this->assertGreaterThan($previousDate, $trend['statisticsDate']);
            }
            $previousDate = $trend['statisticsDate'];
        }
    }

    /**
     * 测试获取汇总统计
     */
    public function test_getSummaryStats_returnsAggregatedSummary(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-10'), 100, 80, 200, 360000, 300000, 5, 85.0, 0.83);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-15'), 150, 120, 300, 540000, 450000, 8, 90.0, 0.83);
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-02-01'), 200, 160, 400, 720000, 600000, 10, 95.0, 0.83);

        $summary = $this->repository->getSummaryStats($startDate, $endDate);

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
    public function test_deleteExpiredRecords_removesOldRecords(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2023-01-01'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2023-06-01'));
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, new \DateTimeImmutable('2024-01-01'));

        $deletedCount = $this->repository->deleteExpiredRecords(new \DateTimeImmutable('2024-01-01'));

        $this->assertEquals(2, $deletedCount);
        
        // 验证只剩下一条记录
        $remaining = $this->repository->findAll();
        $this->assertCount(1, $remaining);
        $this->assertEquals('2024-01-01', $remaining[0]->getStatisticsDate()->format('Y-m-d'));
    }

    /**
     * 测试按类型统计记录数量
     */
    public function test_countByType_returnsTypeCounts(): void
    {
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
            $typeCounts[$count['statisticsType']->value] = $count['count'];
        }
        
        $this->assertEquals(3, $typeCounts[StatisticsType::COURSE->value]);
        $this->assertEquals(2, $typeCounts[StatisticsType::USER->value]);
        $this->assertEquals(1, $typeCounts[StatisticsType::SYSTEM->value]);
    }

    /**
     * 测试按周期统计记录数量
     */
    public function test_countByPeriod_returnsPeriodCounts(): void
    {
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
            $periodCounts[$count['statisticsPeriod']->value] = $count['count'];
        }
        
        $this->assertEquals(3, $periodCounts[StatisticsPeriod::DAILY->value]);
        $this->assertEquals(2, $periodCounts[StatisticsPeriod::HOURLY->value]);
        $this->assertEquals(1, $periodCounts[StatisticsPeriod::MONTHLY->value]);
    }

    /**
     * 测试获取最活跃的统计类型
     */
    public function test_getMostActiveTypes_returnsSortedByActivity(): void
    {
        $this->createTestStatistics(StatisticsType::USER, StatisticsPeriod::DAILY, null, 100, 80, 500);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, null, 200, 150, 1000);
        $this->createTestStatistics(StatisticsType::SYSTEM, StatisticsPeriod::DAILY, null, 50, 40, 200);
        $this->createTestStatistics(StatisticsType::COURSE, StatisticsPeriod::DAILY, null, 150, 120, 800);

        $activeTypes = $this->repository->getMostActiveTypes(2);

        $this->assertCount(2, $activeTypes);
        $this->assertEquals(StatisticsType::COURSE, $activeTypes[0]['statisticsType']);
        $this->assertEquals(1800, $activeTypes[0]['totalSessions']); // 1000 + 800
        $this->assertEquals(StatisticsType::USER, $activeTypes[1]['statisticsType']);
        $this->assertEquals(500, $activeTypes[1]['totalSessions']);
    }

    /**
     * 测试空数据场景
     */
    public function test_findByTypeAndPeriod_withNoData_returnsEmptyArray(): void
    {
        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY);

        $this->assertEmpty($results);
    }

    /**
     * 测试带限制的查询
     */
    public function test_findByTypeAndPeriod_withLimit_returnsLimitedResults(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->createTestStatistics(
                StatisticsType::USER,
                StatisticsPeriod::DAILY,
                new \DateTimeImmutable("2024-01-{$i}")
            );
        }

        $results = $this->repository->findByTypeAndPeriod(StatisticsType::USER, StatisticsPeriod::DAILY, 5);

        $this->assertCount(5, $results);
    }
}