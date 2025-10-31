<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\LearnDevice;

/**
 * @internal
 */
#[CoversClass(LearnDevice::class)]
#[RunTestsInSeparateProcesses]
final class LearnDeviceTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnDevice();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'userId' => ['userId', 'user123'];
        yield 'deviceFingerprint' => ['deviceFingerprint', 'unique-device-fingerprint-123'];
        yield 'deviceName' => ['deviceName', 'iPhone 12 Pro'];
        yield 'deviceType' => ['deviceType', 'Mobile'];
        yield 'operatingSystem' => ['operatingSystem', 'iOS 14.7'];
        yield 'browser' => ['browser', 'Safari'];
        yield 'blockReason' => ['blockReason', '检测到异常行为'];
        yield 'usageCount' => ['usageCount', 5];
        yield 'firstUseTime' => ['firstUseTime', new \DateTimeImmutable()];
        yield 'lastUseTime' => ['lastUseTime', new \DateTimeImmutable()];
        yield 'lastIpAddress' => ['lastIpAddress', '192.168.1.100'];
        yield 'lastUserAgent' => ['lastUserAgent', 'Mozilla/5.0 ...'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testUserIdProperty(): void
    {
        $entity = new LearnDevice();
        $userId = '123456';
        $entity->setUserId($userId);
        $this->assertEquals($userId, $entity->getUserId());
    }

    public function testDeviceFingerprintProperty(): void
    {
        $entity = new LearnDevice();
        $fingerprint = 'unique-device-fingerprint-123';
        $entity->setDeviceFingerprint($fingerprint);
        $this->assertEquals($fingerprint, $entity->getDeviceFingerprint());
    }

    public function testDeviceNameProperty(): void
    {
        $entity = new LearnDevice();
        $deviceName = 'iPhone 12 Pro';
        $entity->setDeviceName($deviceName);
        $this->assertEquals($deviceName, $entity->getDeviceName());
    }

    public function testDeviceTypeProperty(): void
    {
        $entity = new LearnDevice();
        $deviceType = 'Mobile';
        $entity->setDeviceType($deviceType);
        $this->assertEquals($deviceType, $entity->getDeviceType());
    }

    public function testIsActiveProperty(): void
    {
        $entity = new LearnDevice();
        // 测试默认值
        $this->assertTrue($entity->isActive());
        // 测试设置新值
        $entity->setIsActive(false);
        $this->assertFalse($entity->isActive());
    }

    public function testIsTrustedProperty(): void
    {
        $entity = new LearnDevice();
        $this->assertFalse($entity->isTrusted());
        $entity->setIsTrusted(true);
        $this->assertTrue($entity->isTrusted());
    }

    public function testIsBlockedProperty(): void
    {
        $entity = new LearnDevice();
        $this->assertFalse($entity->isBlocked());
        $entity->setIsBlocked(true);
        $entity->setBlockReason('检测到异常行为');
        $this->assertTrue($entity->isBlocked());
        $this->assertEquals('检测到异常行为', $entity->getBlockReason());
    }

    public function testUsageCountProperty(): void
    {
        $entity = new LearnDevice();
        $this->assertEquals(0, $entity->getUsageCount());
        $entity->setUsageCount(5);
        $this->assertEquals(5, $entity->getUsageCount());
    }

    public function testUpdateUsage(): void
    {
        $entity = new LearnDevice();
        $ipAddress = '192.168.1.100';
        $userAgent = 'Mozilla/5.0 ...';
        // 初始状态
        $this->assertNull($entity->getFirstUseTime());
        $this->assertNull($entity->getLastUseTime());
        // 更新使用信息
        $entity->updateUsage($ipAddress, $userAgent);
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getFirstUseTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getLastUseTime());
        $this->assertEquals(1, $entity->getUsageCount());
        $this->assertEquals($ipAddress, $entity->getLastIpAddress());
        $this->assertEquals($userAgent, $entity->getLastUserAgent());
        // 再次更新
        $entity->updateUsage();
        $this->assertEquals(2, $entity->getUsageCount());
    }

    public function testBlock(): void
    {
        $entity = new LearnDevice();
        $reason = '多设备同时登录';
        $entity->block($reason);
        $this->assertTrue($entity->isBlocked());
        $this->assertEquals($reason, $entity->getBlockReason());
    }

    public function testUnblock(): void
    {
        $entity = new LearnDevice();
        // 先阻止
        $entity->block('测试原因');
        // 然后解除阻止
        $entity->unblock();
        $this->assertFalse($entity->isBlocked());
        $this->assertNull($entity->getBlockReason());
    }

    public function testTrustAndUntrust(): void
    {
        $entity = new LearnDevice();
        // 设置为可信设备
        $entity->trust();
        $this->assertTrue($entity->isTrusted());
        // 取消可信设备
        $entity->untrust();
        $this->assertFalse($entity->isTrusted());
    }

    public function testIsAvailable(): void
    {
        $entity = new LearnDevice();
        // 默认情况下可用
        $this->assertTrue($entity->isAvailable());
        // 设置为非活跃
        $entity->setIsActive(false);
        $this->assertFalse($entity->isAvailable());
        // 恢复活跃但阻止
        $entity->setIsActive(true);
        $entity->block('测试');
        $this->assertFalse($entity->isAvailable());
    }

    public function testGetDeviceInfo(): void
    {
        $entity = new LearnDevice();
        // 测试空设备信息
        $this->assertEquals('未知设备', $entity->getDeviceInfo());
        // 测试完整设备信息
        $entity->setDeviceName('iPhone 12');
        $entity->setOperatingSystem('iOS 14');
        $entity->setBrowser('Safari');
        $this->assertEquals('iPhone 12 / iOS 14 / Safari', $entity->getDeviceInfo());
    }
}
