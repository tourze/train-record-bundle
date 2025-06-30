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
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnBehaviorRepository 集成测试
 */
class LearnBehaviorRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnBehaviorRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(LearnBehavior::class);
        $this->assertInstanceOf(LearnBehaviorRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnBehavior::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试行为记录
     */
    private function createTestBehavior(
        string $sessionId = 'session001',
        BehaviorType $behaviorType = BehaviorType::PLAY,
        bool $isSuspicious = false,
        string $userId = 'user001',
        string $deviceFingerprint = 'device001',
        ?\DateTimeInterface $createTime = null
    ): LearnBehavior {
        $behavior = new LearnBehavior();
        $behavior->setSession($sessionId);
        $behavior->setUserId($userId);
        $behavior->setBehaviorType($behaviorType);
        $behavior->setIsSuspicious($isSuspicious);
        $behavior->setDeviceFingerprint($deviceFingerprint);
        $behavior->setMetadata(json_encode(['test' => 'data']));
        
        if ($createTime !== null) {
            // 使用反射设置 createTime（假设它是自动设置的）
            $reflection = new \ReflectionClass($behavior);
            if ($reflection->hasProperty('createTime')) {
                $property = $reflection->getProperty('createTime');
                $property->setAccessible(true);
                $property->setValue($behavior, $createTime);
            }
        }

        $this->entityManager->persist($behavior);
        $this->entityManager->flush();

        return $behavior;
    }

    /**
     * 测试根据会话查找可疑行为
     */
    public function test_findSuspiciousBySession_returnsOnlySuspiciousBehaviors(): void
    {
        $this->createTestBehavior('session001', BehaviorType::RAPID_SEEK, true);
        $this->createTestBehavior('session001', BehaviorType::PLAY, false);
        $this->createTestBehavior('session001', BehaviorType::MULTIPLE_TAB, true);
        $this->createTestBehavior('session002', BehaviorType::FAST_FORWARD, true);

        $results = $this->repository->findSuspiciousBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertTrue($behavior->getIsSuspicious());
            $this->assertEquals('session001', $behavior->getSession());
        }
    }

    /**
     * 测试会话行为类型统计
     */
    public function test_getBehaviorStatsBySession_returnsCorrectStats(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PAUSE);
        $this->createTestBehavior('session001', BehaviorType::SEEK);
        $this->createTestBehavior('session002', BehaviorType::PLAY);

        $stats = $this->repository->getBehaviorStatsBySession('session001');

        $this->assertCount(3, $stats);
        
        $typeStats = [];
        foreach ($stats as $stat) {
            $typeStats[$stat['behaviorType']->value] = $stat['count'];
        }
        
        $this->assertEquals(2, $typeStats[BehaviorType::PLAY->value]);
        $this->assertEquals(1, $typeStats[BehaviorType::PAUSE->value]);
        $this->assertEquals(1, $typeStats[BehaviorType::SEEK->value]);
    }

    /**
     * 测试按时间范围查找行为
     */
    public function test_findByTimeRange_returnsCorrectRecords(): void
    {
        $startTime = new \DateTimeImmutable('2024-01-15 10:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 12:00:00');
        
        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 09:00:00'));
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 10:30:00'));
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 11:30:00'));
        $this->createTestBehavior('session004', BehaviorType::STOP, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 13:00:00'));

        $results = $this->repository->findByTimeRange($startTime, $endTime);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertGreaterThanOrEqual($startTime, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endTime, $behavior->getCreateTime());
        }
    }

    /**
     * 测试根据设备指纹查找行为
     */
    public function test_findByDeviceFingerprint_returnsCorrectRecords(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001');
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user002', 'device001');
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user003', 'device002');

        $results = $this->repository->findByDeviceFingerprint('device001', 10);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('device001', $behavior->getDeviceFingerprint());
        }
    }

    /**
     * 测试根据日期范围查找可疑行为
     */
    public function test_findSuspiciousByDateRange_returnsOnlySuspiciousBehaviors(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestBehavior('session001', BehaviorType::RAPID_SEEK, true, 'user001', 'device001', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session002', BehaviorType::PLAY, false, 'user002', 'device002', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session003', BehaviorType::FAST_FORWARD, true, 'user003', 'device003', new \DateTimeImmutable('2024-01-20'));
        $this->createTestBehavior('session004', BehaviorType::MULTIPLE_TAB, true, 'user004', 'device004', new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findSuspiciousByDateRange($startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertTrue($behavior->getIsSuspicious());
            $this->assertGreaterThanOrEqual($startDate, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $behavior->getCreateTime());
        }
    }

    /**
     * 测试查找会话最近行为
     */
    public function test_findRecentBySession_returnsLimitedRecords(): void
    {
        // 创建15条记录
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestBehavior('session001', BehaviorType::PLAY);
            usleep(1000); // 确保时间差异
        }

        $results = $this->repository->findRecentBySession('session001', 10);

        $this->assertCount(10, $results);
        // 验证按时间降序排列
        $previousTime = null;
        foreach ($results as $behavior) {
            if ($previousTime !== null) {
                $this->assertLessThanOrEqual($previousTime, $behavior->getCreateTime());
            }
            $previousTime = $behavior->getCreateTime();
        }
    }

    /**
     * 测试根据用户和日期范围查找行为
     */
    public function test_findByUserAndDateRange_returnsCorrectRecords(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-20'));
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user002', 'device002', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session004', BehaviorType::STOP, false, 'user001', 'device001', new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findByUserAndDateRange('user001', $startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('user001', $behavior->getUserId());
            $this->assertGreaterThanOrEqual($startDate, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $behavior->getCreateTime());
        }
    }

    /**
     * 测试根据会话查找所有行为
     */
    public function test_findBySession_returnsAllSessionBehaviors(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PAUSE);
        $this->createTestBehavior('session001', BehaviorType::SEEK);
        $this->createTestBehavior('session002', BehaviorType::STOP);

        $results = $this->repository->findBySession('session001');

        $this->assertCount(3, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('session001', $behavior->getSession());
        }
    }

    /**
     * 测试空结果情况
     */
    public function test_findBySession_withNonExistentSession_returnsEmptyArray(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);

        $results = $this->repository->findBySession('non-existent-session');

        $this->assertEmpty($results);
    }

    /**
     * 测试设备指纹限制查询
     */
    public function test_findByDeviceFingerprint_respectsLimit(): void
    {
        // 创建20条记录
        for ($i = 1; $i <= 20; $i++) {
            $this->createTestBehavior('session' . $i, BehaviorType::PLAY, false, 'user001', 'device001');
        }

        $results = $this->repository->findByDeviceFingerprint('device001', 5);

        $this->assertCount(5, $results);
    }

    /**
     * 测试行为类型统计排序
     */
    public function test_getBehaviorStatsBySession_orderedByCountDesc(): void
    {
        // 创建不同数量的行为类型
        for ($i = 0; $i < 5; $i++) {
            $this->createTestBehavior('session001', BehaviorType::PLAY);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createTestBehavior('session001', BehaviorType::PAUSE);
        }
        for ($i = 0; $i < 7; $i++) {
            $this->createTestBehavior('session001', BehaviorType::SEEK);
        }

        $stats = $this->repository->getBehaviorStatsBySession('session001');

        $this->assertCount(3, $stats);
        // 验证按数量降序排列
        $this->assertEquals(BehaviorType::SEEK, $stats[0]['behaviorType']);
        $this->assertEquals(7, $stats[0]['count']);
        $this->assertEquals(BehaviorType::PLAY, $stats[1]['behaviorType']);
        $this->assertEquals(5, $stats[1]['count']);
        $this->assertEquals(BehaviorType::PAUSE, $stats[2]['behaviorType']);
        $this->assertEquals(3, $stats[2]['count']);
    }
}