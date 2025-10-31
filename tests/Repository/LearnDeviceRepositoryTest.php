<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;

/**
 * LearnDeviceRepository 集成测试
 *
 * @template TEntity of LearnDevice
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnDeviceRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnDeviceRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnDeviceRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnDevice::class);
        $this->assertInstanceOf(LearnDeviceRepository::class, $repository);
        $this->repository = $repository;
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
        ?\DateTimeInterface $lastUseTime = null,
        int $usageCount = 0,
    ): LearnDevice {
        $device = new LearnDevice();
        $device->setUserId($userId);
        $device->setDeviceFingerprint($deviceFingerprint);
        $device->setIsActive($isActive);
        $device->setIsBlocked($isBlocked);
        $device->setIsTrusted($isTrusted);
        $device->setLastUseTime($lastUseTime instanceof \DateTimeImmutable ? $lastUseTime :
            (null !== $lastUseTime ? new \DateTimeImmutable($lastUseTime->format('Y-m-d H:i:s')) : new \DateTimeImmutable()));
        $device->setUsageCount($usageCount);
        $device->setDeviceInfo('Chrome - Windows 10');

        self::getEntityManager()->persist($device);
        self::getEntityManager()->flush();

        return $device;
    }

    /**
     * 测试查找用户的活跃设备
     */
    public function testFindActiveByUserReturnsOnlyActiveUnblockedDevices(): void
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
            $this->assertTrue($device->isActive());
            $this->assertFalse($device->isBlocked());
        }
    }

    /**
     * 测试查找用户的活跃设备（带时间阈值）
     */
    public function testFindActiveByUserWithThresholdReturnsRecentlyUsedDevices(): void
    {
        $threshold = new \DateTimeImmutable('-7 days');

        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-3 days'));
        $this->createTestDevice('user001', 'device002', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user001', 'device003', true, false, false, new \DateTimeImmutable('-5 days'));

        $results = $this->repository->findActiveByUser('user001', $threshold);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertGreaterThanOrEqual($threshold, $device->getLastUseTime());
        }
    }

    /**
     * 测试根据指纹查找设备
     */
    public function testFindByFingerprintReturnsCorrectDevice(): void
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
    public function testFindByFingerprintWithNonExistentReturnsNull(): void
    {
        $this->createTestDevice('user001', 'fingerprint001');

        $result = $this->repository->findByFingerprint('non-existent-fingerprint');

        $this->assertNull($result);
    }

    /**
     * 测试查找用户的可信设备
     */
    public function testFindTrustedByUserReturnsOnlyTrustedActiveDevices(): void
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
            $this->assertTrue($device->isTrusted());
            $this->assertTrue($device->isActive());
        }
    }

    /**
     * 测试查找被阻止的设备
     */
    public function testFindBlockedDevicesReturnsOnlyBlockedDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, true);
        $this->createTestDevice('user002', 'device002', true, false);
        $this->createTestDevice('user003', 'device003', true, true);

        $results = $this->repository->findBlockedDevices();

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertTrue($device->isBlocked());
        }
    }

    /**
     * 测试统计用户设备数量
     */
    public function testCountByUserReturnsCorrectCount(): void
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
    public function testFindInactiveDevicesReturnsDevicesBeforeDate(): void
    {
        $beforeDate = new \DateTimeImmutable('-30 days');

        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-60 days'));
        $this->createTestDevice('user002', 'device002', true, false, false, new \DateTimeImmutable('-45 days'));
        $this->createTestDevice('user003', 'device003', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user004', 'device004', false, false, false, new \DateTimeImmutable('-60 days')); // 非活跃

        $results = $this->repository->findInactiveDevices($beforeDate);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertLessThan($beforeDate, $device->getLastUseTime());
            $this->assertTrue($device->isActive());
        }
    }

    /**
     * 测试更新设备最后使用时间
     */
    public function testUpdateLastUsedTimeIncrementsUsageCountAndUpdatesTime(): void
    {
        $device = $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-1 day'), 5);
        $deviceId = $device->getId();
        $this->assertNotNull($deviceId);
        $oldUsageCount = $device->getUsageCount();
        $oldLastUsedTime = $device->getLastUseTime();

        $this->repository->updateLastUsedTime($deviceId);
        self::getEntityManager()->clear();

        $updatedDevice = $this->repository->find($deviceId);
        $this->assertNotNull($updatedDevice);

        $this->assertEquals($oldUsageCount + 1, $updatedDevice->getUsageCount());
        $this->assertGreaterThan($oldLastUsedTime, $updatedDevice->getLastUseTime());
    }

    /**
     * 测试查找所有活跃设备
     */
    public function testFindActiveReturnsOnlyActiveUnblockedDevices(): void
    {
        $this->createTestDevice('user001', 'device001', true, false);
        $this->createTestDevice('user002', 'device002', true, false);
        $this->createTestDevice('user003', 'device003', false, false); // 非活跃
        $this->createTestDevice('user004', 'device004', true, true); // 被阻止

        $results = $this->repository->findActive();

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertTrue($device->isActive());
            $this->assertFalse($device->isBlocked());
        }
    }

    /**
     * 测试根据最后使用时间查找设备
     */
    public function testFindByLastSeenAfterReturnsDevicesAfterDate(): void
    {
        $afterDate = new \DateTimeImmutable('-7 days');

        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-3 days'));
        $this->createTestDevice('user002', 'device002', true, false, false, new \DateTimeImmutable('-10 days'));
        $this->createTestDevice('user003', 'device003', true, false, false, new \DateTimeImmutable('-5 days'));

        $results = $this->repository->findByLastSeenAfter($afterDate);

        $this->assertCount(2, $results);
        foreach ($results as $device) {
            $this->assertGreaterThan($afterDate, $device->getLastUseTime());
        }
    }

    /**
     * 测试根据用户和指纹查找设备
     */
    public function testFindByUserAndFingerprintReturnsCorrectDevice(): void
    {
        $this->createTestDevice('user001', 'fingerprint001');
        $this->createTestDevice('user001', 'fingerprint002');
        $this->createTestDevice('user002', 'fingerprint003'); // 不同指纹，不同用户

        $result = $this->repository->findByUserAndFingerprint('user001', 'fingerprint001');

        $this->assertNotNull($result);
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('fingerprint001', $result->getDeviceFingerprint());
    }

    /**
     * 测试查找用户的所有设备
     */
    public function testFindByUserReturnsAllUserDevices(): void
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
    public function testFindByUserWithNoDevicesReturnsEmptyArray(): void
    {
        $this->createTestDevice('user001', 'device001');

        $results = $this->repository->findByUser('user999');

        $this->assertEmpty($results);
    }

    /**
     * 测试 find 方法
     */
    public function testFind(): void
    {
        $device = $this->createTestDevice('user001', 'device001');
        $deviceId = $device->getId();

        self::getEntityManager()->clear();

        $result = $this->repository->find($deviceId);
        $this->assertInstanceOf(LearnDevice::class, $result);
        $this->assertEquals($deviceId, $result->getId());
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('device001', $result->getDeviceFingerprint());
    }

    /**
     * 测试 find 方法找不到时返回 null
     */
    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find('999999');
        $this->assertNull($result);
    }

    /**
     * 测试 findAll 方法
     */
    public function testFindAll(): void
    {
        $device1 = $this->createTestDevice('user001', 'device001');
        $device2 = $this->createTestDevice('user002', 'device002');

        $results = $this->repository->findAll();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(LearnDevice::class, $results);
    }

    /**
     * 测试 findBy 方法
     */
    public function testFindBy(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, true);
        $this->createTestDevice('user001', 'device002', true, false, false);
        $this->createTestDevice('user002', 'device003', true, false, true);

        $results = $this->repository->findBy(['userId' => 'user001', 'isTrusted' => true]);

        $this->assertCount(1, $results);
        $this->assertEquals('device001', $results[0]->getDeviceFingerprint());
    }

    /**
     * 测试 findBy 方法带排序
     */
    public function testFindByWithOrdering(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, false, new \DateTimeImmutable('-1 day'));
        $this->createTestDevice('user001', 'device002', true, false, false, new \DateTimeImmutable('-2 days'));

        $results = $this->repository->findBy(['userId' => 'user001'], ['lastUseTime' => 'ASC']);

        $this->assertCount(2, $results);
        $this->assertEquals('device002', $results[0]->getDeviceFingerprint());
        $this->assertEquals('device001', $results[1]->getDeviceFingerprint());
    }

    /**
     * 测试 findBy 方法带分页
     */
    public function testFindByWithPagination(): void
    {
        $this->createTestDevice('user001', 'device001');
        $this->createTestDevice('user001', 'device002');
        $this->createTestDevice('user001', 'device003');

        $results = $this->repository->findBy(['userId' => 'user001'], null, 2, 1);

        $this->assertCount(2, $results);
    }

    /**
     * 测试 findOneBy 方法
     */
    public function testFindOneBy(): void
    {
        $this->createTestDevice('user001', 'unique-device');
        $this->createTestDevice('user002', 'another-device');

        $result = $this->repository->findOneBy(['deviceFingerprint' => 'unique-device']);

        $this->assertInstanceOf(LearnDevice::class, $result);
        $this->assertEquals('unique-device', $result->getDeviceFingerprint());
        $this->assertEquals('user001', $result->getUserId());
    }

    /**
     * 测试 findOneBy 方法找不到时返回 null
     */
    public function testFindOneByWithNonExistentCriteria(): void
    {
        $this->createTestDevice('user001', 'device001');

        $result = $this->repository->findOneBy(['deviceFingerprint' => 'non-existent']);

        $this->assertNull($result);
    }

    /**
     * 测试 count 方法
     */
    public function testCount(): void
    {
        $this->createTestDevice('user001', 'device001');
        $this->createTestDevice('user002', 'device002');

        $count = $this->repository->count();

        $this->assertEquals(2, $count);
    }

    /**
     * 测试带条件的 count 方法
     */
    public function testCountWithCriteria(): void
    {
        $this->createTestDevice('user001', 'device001', true, false);
        $this->createTestDevice('user001', 'device002', false, false);
        $this->createTestDevice('user002', 'device003', true, false);

        $count = $this->repository->count(['userId' => 'user001', 'isActive' => true]);

        $this->assertEquals(1, $count);
    }

    /**
     * 测试 save 方法
     */
    public function testSave(): void
    {
        $device = new LearnDevice();
        $device->setUserId('user001');
        $device->setDeviceFingerprint('test-fingerprint');
        $device->setDeviceInfo('Test Device');

        $this->repository->save($device);

        $this->assertNotNull($device->getId());

        $savedDevice = $this->repository->find($device->getId());
        $this->assertNotNull($savedDevice);
        $this->assertEquals('user001', $savedDevice->getUserId());
        $this->assertEquals('test-fingerprint', $savedDevice->getDeviceFingerprint());
    }

    /**
     * 测试不刷新的 save 方法
     */
    public function testSaveWithoutFlush(): void
    {
        $device = new LearnDevice();
        $device->setUserId('user001');
        $device->setDeviceFingerprint('test-fingerprint');
        $device->setDeviceInfo('Test Device');

        $this->repository->save($device, false);
        self::getEntityManager()->flush();

        $this->assertNotNull($device->getId());
        $savedDevice = $this->repository->find($device->getId());
        $this->assertNotNull($savedDevice);
        $this->assertEquals('user001', $savedDevice->getUserId());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemove(): void
    {
        $device = $this->createTestDevice('user001', 'device001');
        $deviceId = $device->getId();

        $this->repository->remove($device);

        $removedDevice = $this->repository->find($deviceId);
        $this->assertNull($removedDevice);
    }

    /**
     * 测试不刷新的 remove 方法
     */
    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        $device = $this->createTestDevice('user001', 'device001');
        $deviceId = $device->getId();

        $this->repository->remove($device, false);
        self::getEntityManager()->flush();

        $removedDevice = $this->repository->find($deviceId);
        $this->assertNull($removedDevice);
    }

    /**
     * 测试可空字段查询
     */
    public function testFindByNullableField(): void
    {
        $device1 = $this->createTestDevice('user001', 'device001');
        $device1->setDeviceName('iPhone');
        self::getEntityManager()->persist($device1);

        $device2 = $this->createTestDevice('user002', 'device002');
        $device2->setDeviceName(null);
        self::getEntityManager()->persist($device2);

        self::getEntityManager()->flush();

        $withName = $this->repository->findBy(['deviceName' => 'iPhone']);
        $this->assertCount(1, $withName);
        $this->assertEquals('device001', $withName[0]->getDeviceFingerprint());

        $withoutName = $this->repository->findBy(['deviceName' => null]);
        $this->assertCount(1, $withoutName);
        $this->assertEquals('device002', $withoutName[0]->getDeviceFingerprint());
    }

    /**
     * 测试时间字段查询
     */
    public function testFindByDateTimeField(): void
    {
        $date1 = new \DateTimeImmutable('-1 day');
        $date2 = new \DateTimeImmutable('-2 days');

        $this->createTestDevice('user001', 'device001', true, false, false, $date1);
        $this->createTestDevice('user002', 'device002', true, false, false, $date2);

        $results = $this->repository->findBy(['lastUseTime' => $date1]);

        $this->assertCount(1, $results);
        $this->assertEquals('device001', $results[0]->getDeviceFingerprint());
    }

    /**
     * 测试布尔字段查询
     */
    public function testFindByBooleanField(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, true);
        $this->createTestDevice('user002', 'device002', true, false, false);
        $this->createTestDevice('user003', 'device003', false, false, true);

        $trustedDevices = $this->repository->findBy(['isTrusted' => true]);
        $this->assertCount(2, $trustedDevices);

        $untrustedDevices = $this->repository->findBy(['isTrusted' => false]);
        $this->assertCount(1, $untrustedDevices);
        $this->assertEquals('device002', $untrustedDevices[0]->getDeviceFingerprint());
    }

    /**
     * 测试多条件组合查询
     */
    public function testFindByMultipleCriteria(): void
    {
        $this->createTestDevice('user001', 'device001', true, false, true);
        $this->createTestDevice('user001', 'device002', true, false, false);
        $this->createTestDevice('user001', 'device003', false, false, true);
        $this->createTestDevice('user002', 'device004', true, false, true);

        $results = $this->repository->findBy([
            'userId' => 'user001',
            'isActive' => true,
            'isTrusted' => true,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('device001', $results[0]->getDeviceFingerprint());
    }

    /**
     * 测试 findBy 方法分页功能
     */

    /**
     * 测试 findBy 方法带匹配条件
     */

    /**
     * 测试 findBy 方法排序功能
     */

    /**
     * 测试 findOneBy 方法带匹配条件
     */

    /**
     * 测试 findAll 方法带有记录
     */

    /**
     * 测试 findOneBy 方法排序功能
     */

    /**
     * 测试 find 方法找到现有实体
     */

    /**
     * 测试 findOneBy 可空字段查询
     */

    /**
     * 测试 findBy 可空字段查询返回所有匹配实体
     */

    /**
     * 测试 count 可空字段查询
     */
    protected function createNewEntity(): object
    {
        $entity = new LearnDevice();
        $entity->setUserId('test-user-' . uniqid());
        $entity->setDeviceFingerprint('test-fingerprint-' . uniqid());

        return $entity;
    }

    /** @return ServiceEntityRepository<LearnDevice> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
