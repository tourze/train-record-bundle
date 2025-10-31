<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Service\LearnDeviceService;

/**
 * @internal
 */
#[CoversClass(LearnDeviceService::class)]
#[RunTestsInSeparateProcesses]
final class LearnDeviceServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(LearnDeviceService::class);
        $this->assertInstanceOf(LearnDeviceService::class, $service);
    }

    public function testRegisterDevice(): void
    {
        $service = self::getService(LearnDeviceService::class);

        $deviceInfo = [
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'screen' => '1920x1080',
            'timezone' => 'Asia/Shanghai',
            'language' => 'zh-CN',
            'platform' => 'Win32',
            'device' => ['type' => 'desktop'],
            'browser' => ['name' => 'Chrome'],
            'os' => ['name' => 'Windows'],
        ];

        $result = $service->registerDevice('test-user-id', $deviceInfo);
        $this->assertInstanceOf(LearnDevice::class, $result);
    }

    public function testGetActiveDevices(): void
    {
        $service = self::getService(LearnDeviceService::class);

        $result = $service->getActiveDevices('test-user-id');
        $this->assertIsArray($result);
    }

    public function testIsDeviceTrusted(): void
    {
        $service = self::getService(LearnDeviceService::class);

        $result = $service->isDeviceTrusted('test-user-id', 'test-fingerprint');
        $this->assertIsBool($result);
    }

    public function testTrustDeviceUpdatesDeviceStatus(): void
    {
        $service = self::getService(LearnDeviceService::class);

        // 先注册一个设备
        $deviceInfo = [
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'screen' => '1920x1080',
            'device' => ['type' => 'desktop'],
            'browser' => ['name' => 'Chrome'],
            'os' => ['name' => 'Windows'],
        ];

        $device = $service->registerDevice('test-user-trust', $deviceInfo);

        // 验证初始状态不是受信任的
        $this->assertFalse($device->isTrusted());

        // 标记设备为受信任
        $service->trustDevice('test-user-trust', $device->getDeviceFingerprint());

        // 验证设备已被标记为受信任
        $this->assertTrue($service->isDeviceTrusted('test-user-trust', $device->getDeviceFingerprint()));
    }

    public function testRecordSuspiciousActivityLogsActivity(): void
    {
        $service = self::getService(LearnDeviceService::class);

        // 先注册一个设备并设为可信
        $deviceInfo = [
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device' => ['type' => 'desktop'],
            'browser' => ['name' => 'Chrome'],
            'os' => ['name' => 'Windows'],
        ];

        $device = $service->registerDevice('test-user-suspicious', $deviceInfo);
        $service->trustDevice('test-user-suspicious', $device->getDeviceFingerprint());

        // 验证设备初始状态是受信任的
        $this->assertTrue($service->isDeviceTrusted('test-user-suspicious', $device->getDeviceFingerprint()));

        // 记录可疑活动
        $service->recordSuspiciousActivity('test-user-suspicious', $device->getDeviceFingerprint(), 'Multiple login attempts');

        // 验证设备不再受信任（可疑活动应该取消设备的信任状态）
        $this->assertFalse($service->isDeviceTrusted('test-user-suspicious', $device->getDeviceFingerprint()));
    }

    public function testDeactivateDeviceUpdatesDeviceStatus(): void
    {
        $service = self::getService(LearnDeviceService::class);

        // 先注册一个设备
        $deviceInfo = [
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device' => ['type' => 'desktop'],
            'browser' => ['name' => 'Chrome'],
            'os' => ['name' => 'Windows'],
        ];

        $device = $service->registerDevice('test-user-deactivate', $deviceInfo);

        // 验证设备初始状态是活跃的
        $this->assertTrue($device->isActive());

        // 停用设备
        $service->deactivateDevice('test-user-deactivate', $device->getDeviceFingerprint());

        // 重新加载设备实体验证状态
        $em = self::getService(EntityManagerInterface::class);
        $em->refresh($device);
        $this->assertFalse($device->isActive());
    }

    public function testGetDeviceStatsReturnsValidArray(): void
    {
        $service = self::getService(LearnDeviceService::class);

        // 先注册一些设备以生成统计数据
        $deviceInfo1 = [
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device' => ['type' => 'desktop'],
            'browser' => ['name' => 'Chrome'],
            'os' => ['name' => 'Windows'],
        ];

        $deviceInfo2 = [
            'userAgent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            'device' => ['type' => 'mobile'],
            'browser' => ['name' => 'Safari'],
            'os' => ['name' => 'iOS'],
        ];

        $service->registerDevice('test-user-stats', $deviceInfo1);
        $service->registerDevice('test-user-stats', $deviceInfo2);

        // 获取设备统计
        $result = $service->getDeviceStats('test-user-stats');
        $this->assertIsArray($result);

        // 验证返回的统计数据包含期望的字段
        $this->assertArrayHasKey('total_devices', $result);
        $this->assertArrayHasKey('active_devices', $result);
        $this->assertArrayHasKey('trusted_devices', $result);

        // 验证统计数据的类型
        $this->assertIsInt($result['total_devices']);
        $this->assertIsInt($result['active_devices']);
        $this->assertIsInt($result['trusted_devices']);

        // 验证数据的逻辑正确性
        $this->assertGreaterThanOrEqual(0, $result['total_devices']);
        $this->assertGreaterThanOrEqual(0, $result['active_devices']);
        $this->assertGreaterThanOrEqual(0, $result['trusted_devices']);
    }
}
