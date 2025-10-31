<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Service\LearnAnomalyService;

/**
 * @internal
 */
#[CoversClass(LearnAnomalyService::class)]
#[RunTestsInSeparateProcesses]
final class LearnAnomalyServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(LearnAnomalyService::class);
        $this->assertInstanceOf(LearnAnomalyService::class, $service);
    }

    public function testDetectMultipleDeviceAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $userId = 'user123';

        $result = $service->detectMultipleDeviceAnomaly($userId);

        $this->assertIsArray($result);
    }

    public function testDetectRapidProgressAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'invalid_session_id';
        $speedThreshold = 2.0;

        $result = $service->detectRapidProgressAnomaly($sessionId, $speedThreshold);

        $this->assertNull($result);
    }

    public function testDetectWindowSwitchAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'invalid_session_id';
        $switchThreshold = 10;

        $result = $service->detectWindowSwitchAnomaly($sessionId, $switchThreshold);

        $this->assertNull($result);
    }

    public function testDetectIdleTimeoutAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'invalid_session_id';
        $timeoutSeconds = 300;

        $result = $service->detectIdleTimeoutAnomaly($sessionId, $timeoutSeconds);

        $this->assertNull($result);
    }

    public function testDetectFaceDetectFailAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'invalid_session_id';
        $failThreshold = 5;

        $result = $service->detectFaceDetectFailAnomaly($sessionId, $failThreshold);

        $this->assertNull($result);
    }

    public function testDetectNetworkAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'invalid_session_id';
        $networkData = ['latency' => 1000, 'packet_loss' => 0.05];

        $result = $service->detectNetworkAnomaly($sessionId, $networkData);

        $this->assertNull($result);
    }

    public function testResolveAnomalyWithValidData(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        // 创建一个测试异常记录 - 使用 Mock 对象避免复杂依赖
        $anomaly = $this->createMock(LearnAnomaly::class);
        $anomaly->method('getId')->willReturn('test-anomaly-id');

        // 验证异常状态被正确设置
        $anomaly->expects($this->once())
            ->method('setStatus')
            ->with(AnomalyStatus::RESOLVED)
        ;

        // 验证解决方案被设置
        $anomaly->expects($this->once())
            ->method('setResolution')
            ->with('已验证为正常操作')
        ;

        // 验证解决人被设置
        $anomaly->expects($this->once())
            ->method('setResolvedBy')
            ->with('test_admin')
        ;

        // 验证解决时间被设置
        $anomaly->expects($this->once())
            ->method('setResolveTime')
            ->with(self::isInstanceOf(\DateTimeImmutable::class))
        ;

        // Mock Repository
        $repository = $this->createMock(LearnAnomalyRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('test-anomaly-id')
            ->willReturn($anomaly)
        ;

        // 使用反射设置私有属性
        $reflection = new \ReflectionClass($service);
        $repositoryProperty = $reflection->getProperty('anomalyRepository');
        $repositoryProperty->setAccessible(true);
        $repositoryProperty->setValue($service, $repository);

        $anomalyId = 'test-anomaly-id';
        $resolution = '已验证为正常操作';
        $resolvedBy = 'test_admin';

        // 解决异常 - 应该不会抛出异常
        $service->resolveAnomaly($anomalyId, $resolution, $resolvedBy);
    }

    public function testResolveAnomalyWithInvalidId(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        // Mock Repository 返回 null（模拟不存在的异常）
        $repository = $this->createMock(LearnAnomalyRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with('invalid-anomaly-id')
            ->willReturn(null)
        ;

        // 使用反射设置私有属性
        $reflection = new \ReflectionClass($service);
        $repositoryProperty = $reflection->getProperty('anomalyRepository');
        $repositoryProperty->setAccessible(true);
        $repositoryProperty->setValue($service, $repository);

        // 测试不存在的异常ID - 应该静默处理，不抛出异常
        $service->resolveAnomaly('invalid-anomaly-id', '测试解决', 'test_user');

        // 如果执行到这里说明方法正确处理了不存在的ID情况
        // 验证没有抛出异常就是测试通过
    }

    public function testDetectAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $sessionId = 'session123';
        $behaviorData = ['click_count' => 100, 'time_spent' => 3600];

        $result = $service->detectAnomaly($sessionId, $behaviorData);

        $this->assertNull($result);
    }

    public function testClassifyAnomaly(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $anomalyData = ['type' => 'suspicious', 'score' => 0.8];

        $result = $service->classifyAnomaly($anomalyData);

        $this->assertIsString($result);
        $this->assertEquals('suspicious_behavior', $result);
    }

    public function testGetAnomalyReport(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $userId = 'user123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $service->getAnomalyReport($userId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('total_anomalies', $result);
        $this->assertEquals($userId, $result['user_id']);
        $this->assertEquals(0, $result['total_anomalies']);
    }

    public function testGetAnomalyTrends(): void
    {
        $service = self::getService(LearnAnomalyService::class);

        $result = $service->getAnomalyTrends();

        $this->assertIsArray($result);
    }
}
