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
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnAnomalyRepository 集成测试
 */
class LearnAnomalyRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnAnomalyRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(LearnAnomaly::class);
        $this->assertInstanceOf(LearnAnomalyRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnAnomaly::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试异常记录
     */
    private function createTestAnomaly(
        string $sessionId = 'session001',
        AnomalyType $type = AnomalyType::RAPID_PROGRESS,
        AnomalySeverity $severity = AnomalySeverity::MEDIUM,
        AnomalyStatus $status = AnomalyStatus::DETECTED,
        ?\DateTimeInterface $detectedTime = null,
        bool $isAutoDetected = true
    ): LearnAnomaly {
        $anomaly = new LearnAnomaly();
        $anomaly->setSession($this->createMockSession($sessionId));
        $anomaly->setAnomalyType($type);
        $anomaly->setSeverity($severity);
        $anomaly->setStatus($status);
        $anomaly->setDetectedTime($detectedTime instanceof \DateTimeImmutable ? $detectedTime : new \DateTimeImmutable($detectedTime?->format('Y-m-d H:i:s') ?? 'now'));
        $anomaly->setIsAutoDetected($isAutoDetected);
        $anomaly->setAnomalyDescription('Test anomaly description');
        $anomaly->setEvidence(['test' => 'evidence']);

        if ($status === AnomalyStatus::RESOLVED) {
            $anomaly->setResolvedTime(new \DateTimeImmutable());
            $anomaly->setResolution('测试解决方案');
        }

        $this->entityManager->persist($anomaly);
        $this->entityManager->flush();

        return $anomaly;
    }

    /**
     * 创建模拟学习会话
     */
    private function createMockSession(string $sessionId): LearnSession
    {
        $session = new LearnSession();
        // 使用反射设置 ID
        $reflection = new \ReflectionClass($session);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($session, $sessionId);
        }
        return $session;
    }

    /**
     * 测试根据会话查找异常
     */
    public function test_findBySession_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS);
        $this->createTestAnomaly('session001', AnomalyType::MULTIPLE_DEVICE);
        $this->createTestAnomaly('session002', AnomalyType::IDLE_TIMEOUT);

        $results = $this->repository->findBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertEquals('session001', $anomaly->getSession());
        }
    }

    /**
     * 测试查找未处理的异常
     */
    public function test_findUnprocessed_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::MEDIUM, AnomalyStatus::INVESTIGATING);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::RESOLVED);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::HIGH, AnomalyStatus::IGNORED);

        $results = $this->repository->findUnprocessed();

        $this->assertCount(2, $results);
        $statuses = array_map(fn($a) => $a->getStatus(), $results);
        $this->assertContains(AnomalyStatus::DETECTED, $statuses);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $statuses);
    }

    /**
     * 测试查找高优先级异常
     */
    public function test_findHighPriority_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::CRITICAL, AnomalyStatus::DETECTED);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::HIGH, AnomalyStatus::INVESTIGATING);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::MEDIUM, AnomalyStatus::DETECTED);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::HIGH, AnomalyStatus::RESOLVED);

        $results = $this->repository->findHighPriority();

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertContains($anomaly->getSeverity(), [AnomalySeverity::HIGH, AnomalySeverity::CRITICAL]);
            $this->assertNotContains($anomaly->getStatus(), [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED]);
        }
    }

    /**
     * 测试按类型统计异常
     */
    public function test_getAnomalyStatsByType_returnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::MEDIUM, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session002', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session003', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session004', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::DETECTED, new \DateTimeImmutable('2024-02-01'));

        $stats = $this->repository->getAnomalyStatsByType($startDate, $endDate);

        $this->assertCount(2, $stats);
        
        $typeStats = [];
        foreach ($stats as $stat) {
            $typeStats[$stat['anomalyType']->value] = $stat['count'];
        }
        
        $this->assertEquals(2, $typeStats[AnomalyType::RAPID_PROGRESS->value]);
        $this->assertEquals(1, $typeStats[AnomalyType::MULTIPLE_DEVICE->value]);
    }

    /**
     * 测试按严重程度统计异常
     */
    public function test_getAnomalyStatsBySeverity_returnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::MEDIUM, AnomalyStatus::DETECTED, $startDate);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::LOW, AnomalyStatus::DETECTED, $startDate);

        $stats = $this->repository->getAnomalyStatsBySeverity($startDate, $endDate);

        $this->assertCount(3, $stats);
        
        $severityStats = [];
        foreach ($stats as $stat) {
            $severityStats[$stat['severity']->value] = $stat['count'];
        }
        
        $this->assertEquals(2, $severityStats[AnomalySeverity::HIGH->value]);
        $this->assertEquals(1, $severityStats[AnomalySeverity::MEDIUM->value]);
        $this->assertEquals(1, $severityStats[AnomalySeverity::LOW->value]);
    }

    /**
     * 测试查找自动检测的异常
     */
    public function test_findAutoDetected_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, null, true);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::MEDIUM, AnomalyStatus::DETECTED, null, true);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::DETECTED, null, false);

        $results = $this->repository->findAutoDetected(10);

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertTrue($anomaly->getIsAutoDetected());
        }
    }

    /**
     * 测试查找特定类型的异常
     */
    public function test_findByType_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS);
        $this->createTestAnomaly('session002', AnomalyType::RAPID_PROGRESS);
        $this->createTestAnomaly('session003', AnomalyType::MULTIPLE_DEVICE);

        $results = $this->repository->findByType(AnomalyType::RAPID_PROGRESS, 10);

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertEquals(AnomalyType::RAPID_PROGRESS, $anomaly->getAnomalyType());
        }
    }

    /**
     * 测试查找处理时间过长的异常
     */
    public function test_findLongProcessing_returnsCorrectRecords(): void
    {
        $oldDate = new \DateTimeImmutable('-7 days');
        $recentDate = new \DateTimeImmutable('-1 day');
        $threshold = new \DateTimeImmutable('-3 days');

        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $oldDate);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::MEDIUM, AnomalyStatus::INVESTIGATING, $oldDate);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::DETECTED, $recentDate);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::HIGH, AnomalyStatus::RESOLVED, $oldDate);

        $results = $this->repository->findLongProcessing($threshold);

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertLessThan($threshold, $anomaly->getDetectedTime());
            $this->assertContains($anomaly->getStatus(), [AnomalyStatus::DETECTED, AnomalyStatus::INVESTIGATING]);
        }
    }

    /**
     * 测试处理效率统计
     */
    public function test_getProcessingEfficiencyStats_returnsCorrectStats(): void
    {
        $detectedTime = new \DateTimeImmutable('-2 hours');
        $resolvedTime = new \DateTimeImmutable();

        $anomaly1 = $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::RESOLVED);
        $anomaly1->setDetectedTime($detectedTime);
        $anomaly1->setResolvedTime($resolvedTime);
        
        $anomaly2 = $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::MEDIUM, AnomalyStatus::RESOLVED);
        $anomaly2->setDetectedTime($detectedTime);
        $anomaly2->setResolvedTime($resolvedTime);
        
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::IGNORED);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::HIGH, AnomalyStatus::DETECTED);
        
        $this->entityManager->flush();

        $stats = $this->repository->getProcessingEfficiencyStats();

        $this->assertEquals(4, $stats['totalAnomalies']);
        $this->assertEquals(2, $stats['resolvedCount']);
        $this->assertEquals(1, $stats['ignoredCount']);
        $this->assertNotNull($stats['avgProcessingTime']);
    }

    /**
     * 测试查找未解决的异常
     */
    public function test_findUnresolved_returnsCorrectRecords(): void
    {
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::CRITICAL, AnomalyStatus::DETECTED);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::HIGH, AnomalyStatus::INVESTIGATING);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::MEDIUM, AnomalyStatus::RESOLVED);
        $this->createTestAnomaly('session004', AnomalyType::SUSPICIOUS_BEHAVIOR, AnomalySeverity::LOW, AnomalyStatus::IGNORED);

        $results = $this->repository->findUnresolved();

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertNotContains($anomaly->getStatus(), [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED]);
        }
    }

    /**
     * 测试按日期范围查找异常
     */
    public function test_findByDateRange_returnsCorrectRecords(): void
    {
        $date1 = new \DateTimeImmutable('2024-01-10');
        $date2 = new \DateTimeImmutable('2024-01-20');
        $date3 = new \DateTimeImmutable('2024-02-01');
        
        $this->createTestAnomaly('session001', AnomalyType::RAPID_PROGRESS, AnomalySeverity::HIGH, AnomalyStatus::DETECTED, $date1);
        $this->createTestAnomaly('session002', AnomalyType::MULTIPLE_DEVICE, AnomalySeverity::MEDIUM, AnomalyStatus::DETECTED, $date2);
        $this->createTestAnomaly('session003', AnomalyType::IDLE_TIMEOUT, AnomalySeverity::LOW, AnomalyStatus::DETECTED, $date3);

        $results = $this->repository->findByDateRange(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-01-31')
        );

        $this->assertCount(2, $results);
        foreach ($results as $anomaly) {
            $this->assertGreaterThanOrEqual('2024-01-01', $anomaly->getDetectedTime()->format('Y-m-d'));
            $this->assertLessThanOrEqual('2024-01-31', $anomaly->getDetectedTime()->format('Y-m-d'));
        }
    }
}