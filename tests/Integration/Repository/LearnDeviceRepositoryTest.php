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
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnDeviceRepository 集成测试
 */
class LearnDeviceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnDeviceRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(LearnDevice::class);
        $this->assertInstanceOf(LearnDeviceRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnDevice::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试设备
     */
    private function createTestDevice(
        string $userId = 'user001',
        string $deviceFingerprint = 'device001',
        bool $isActive = true,
        bool $isBlocked = false,
        bool $isTrusted = false,
        ?\DateTimeInterface $lastUsedTime = null,
        int $usageCount = 0
    ): LearnDevice {
        $device = new LearnDevice();
        $device->setUserId($userId);
        $device->setDeviceFingerprint($deviceFingerprint);
        $device->setIsActive($isActive);
        $device->setIsBlocked($isBlocked);
        $device->setIsTrusted($isTrusted);
        $device->setLastUsedTime($lastUsedTime ?? new \DateTimeImmutable());
        $device->setUsageCount($usageCount);
        $device->setDeviceInfo(json_encode([
            'browser' => 'Chrome',
            'os' => 'Windows 10'
        ]));

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return $device;
    }

    /**
     * 测试查找用户的活跃设备
     */
    public function test_findActiveByUser_returnsOnlyActiveUnblockedDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, false);
        $this->createTestDevice('user001', 'device002', true, false);
        $this->createTestDevice('user001', 'device003', false, false); // 非活跃
        $this->createTestDevice('user001', 'device004', true, true); // 被阻止
        $this->createTestDevice('user002', 'device005', true, false);

        $results = $this->repository->findActiveByUser('user001');

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertEquals('user001', $device->getUserId());
            $this->assertTrue($device->getIsActive());
            $this->assertFalse($device->getIsBlocked());
        }
    }

    /**
     * 测试查找用户的活跃设备（带时间阈值）
     */
    public function test_findActiveByUser_withThreshold_returnsRecentlyUsedDevices(): void
    {
        $threshold = new \DateTimeImmutable('-7 days');
        
        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-3 days'));
        $this->createTestDevice('user001', 'device002', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user001', 'device003', true, false, false, new \DateTimeImmutable('-5 days'));

        $results = $this->repository->findActiveByUser('user001', $threshold);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertGreaterThanOrEqual($threshold, $device->getLastUsedTime());
        }
    }

    /**
     * 测试根据指纹查找设备
     */
    public function test_findByFingerprint_returnsCorrectDevice(): void
    {
        $this->createTestDevice('user001', 'unique-fingerprint-123');
        $this->createTestDevice('user002', 'unique-fingerprint-456');

        $result = $this->repository->findByFingerprint('unique-fingerprint-123');

        $this->assertNotNull($result);
        $this->assertEquals('unique-fingerprint-123', $result->getDeviceFingerprint());
        $this->assertEquals('user001', $result->getUserId());
    }

    /**
     * 测试根据不存在的指纹查找设备
     */
    public function test_findByFingerprint_withNonExistent_returnsNull(): void
    {
        $this->createTestDevice('user001', 'fingerprint001');

        $result = $this->repository->findByFingerprint('non-existent-fingerprint');

        $this->assertNull($result);
    }

    /**
     * 测试查找用户的可信设备
     */
    public function test_findTrustedByUser_returnsOnlyTrustedActiveDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, true);
        $this->createTestDevice('user001', 'device002', true, false, false); // 不可信
        $this->createTestDevice('user001', 'device003', false, false, true); // 非活跃
        $this->createTestDevice('user001', 'device004', true, false, true);
        $this->createTestDevice('user002', 'device005', true, false, true);

        $results = $this->repository->findTrustedByUser('user001');

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertEquals('user001', $device->getUserId());
            $this->assertTrue($device->getIsTrusted());
            $this->assertTrue($device->getIsActive());
        }
    }

    /**
     * 测试查找被阻止的设备
     */
    public function test_findBlockedDevices_returnsOnlyBlockedDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, true);
        $this->createTestDevice('user002', 'device002', true, false);
        $this->createTestDevice('user003', 'device003', true, true);

        $results = $this->repository->findBlockedDevices();

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertTrue($device->getIsBlocked());
        }
    }

    /**
     * 测试统计用户设备数量
     */
    public function test_countByUser_returnsCorrectCount(): void
    {
        $this->createTestDevice('user001', 'device001', true, false);
        $this->createTestDevice('user001', 'device002', true, false);
        $this->createTestDevice('user001', 'device003', false, false); // 非活跃，不计入
        $this->createTestDevice('user002', 'device004', true, false);

        $count = $this->repository->countByUser('user001');

        $this->assertEquals(2, $count);
    }

    /**
     * 测试查找长时间未使用的设备
     */
    public function test_findInactiveDevices_returnsDevicesBeforeDate(): void
    {
        $beforeDate = new \DateTimeImmutable('-30 days');
        
        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-60 days'));
        $this->createTestDevice('user002', 'device002', true, false, false, new \DateTimeImmutable('-45 days'));
        $this->createTestDevice('user003', 'device003', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user004', 'device004', false, false, false, new \DateTimeImmutable('-60 days')); // 非活跃

        $results = $this->repository->findInactiveDevices($beforeDate);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertLessThan($beforeDate, $device->getLastUsedTime());
            $this->assertTrue($device->getIsActive());
        }
    }

    /**
     * 测试更新设备最后使用时间
     */
    public function test_updateLastUsedTime_incrementsUsageCountAndUpdatesTime(): void
    {
        $device = $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-1 day'), 5);
        $deviceId = $device->getId();
        $oldUsageCount = $device->getUsageCount();
        $oldLastUsedTime = $device->getLastUsedTime();

        $this->repository->updateLastUsedTime($deviceId);
        $this->entityManager->clear();

        $updatedDevice = $this->repository->find($deviceId);

        $this->assertEquals($oldUsageCount + 1, $updatedDevice->getUsageCount());
        $this->assertGreaterThan($oldLastUsedTime, $updatedDevice->getLastUsedTime());
    }

    /**
     * 测试查找所有活跃设备
     */
    public function test_findActive_returnsOnlyActiveUnblockedDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, false);
        $this->createTestDevice('user002', 'device002', true, false);
        $this->createTestDevice('user003', 'device003', false, false); // 非活跃
        $this->createTestDevice('user004', 'device004', true, true); // 被阻止

        $results = $this->repository->findActive();

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertTrue($device->getIsActive());
            $this->assertFalse($device->getIsBlocked());
        }
    }

    /**
     * 测试根据最后使用时间查找设备
     */
    public function test_findByLastSeenAfter_returnsDevicesAfterDate(): void
    {
        $afterDate = new \DateTimeImmutable('-7 days');
        
        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-3 days'));
        $this->createTestDevice('user002', 'device002', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user003', 'device003', true, false, false, new \DateTimeImmutable('-5 days'));

        $results = $this->repository->findByLastSeenAfter($afterDate);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertGreaterThan($afterDate, $device->getLastUsedTime());
        }
    }

    /**
     * 测试根据用户和指纹查找设备
     */
    public function test_findByUserAndFingerprint_returnsCorrectDevice(): void
    {
        $this->createTestDevice('user001', 'fingerprint001');
        $this->createTestDevice('user001', 'fingerprint002');
        $this->createTestDevice('user002', 'fingerprint001'); // 相同指纹，不同用户

        $result = $this->repository->findByUserAndFingerprint('user001', 'fingerprint001');

        $this->assertNotNull($result);
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('fingerprint001', $result->getDeviceFingerprint());
    }

    /**
     * 测试查找用户的所有设备
     */
    public function test_findByUser_returnsAllUserDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-1 day'));
        $this->createTestDevice('user001', 'device002', false, true, false, new \DateTimeImmutable('-3 days'));
        $this->createTestDevice('user001', 'device003', true, false, true, new \DateTimeImmutable('-2 days'));
        $this->createTestDevice('user002', 'device004');

        $results = $this->repository->findByUser('user001');

        $this->assertCount(3, $results);
        foreach ($results as $device) {
            $this->assertEquals('user001', $device->getUserId());
        }
        
        // 验证按最后使用时间降序排列
        $this->assertEquals('device001', $results[0]->getDeviceFingerprint());
        $this->assertEquals('device003', $results[1]->getDeviceFingerprint());
        $this->assertEquals('device002', $results[2]->getDeviceFingerprint());
    }

    /**
     * 测试空用户设备列表
     */
    public function test_findByUser_withNoDevices_returnsEmptyArray(): void
    {
        $this->createTestDevice('user001', 'device001');

        $results = $this->repository->findByUser('user999');

        $this->assertEmpty($results);
    }
}