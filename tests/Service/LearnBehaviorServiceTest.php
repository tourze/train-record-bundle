<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;

/**
 * @internal
 */
#[CoversClass(LearnBehaviorService::class)]
#[RunTestsInSeparateProcesses]
final class LearnBehaviorServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(LearnBehaviorService::class);
        $this->assertInstanceOf(LearnBehaviorService::class, $service);
    }

    public function testRecordBehavior(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        // Mock session data - this test would need proper mocking setup
        $this->expectException(\Exception::class);
        $service->recordBehavior('test-session-id', 'PLAY', ['videoTimestamp' => '10.5']);
    }

    public function testGetBehaviorStatsBySession(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        $result = $service->getBehaviorStatsBySession('test-session-id');
        $this->assertIsArray($result);
    }

    public function testAnalyzeBehaviorPatternWithValidData(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        $startDate = new \DateTimeImmutable('2023-01-01');
        $endDate = new \DateTimeImmutable('2023-12-31');

        // 测试分析行为模式功能
        $result = $service->analyzeBehaviorPattern('test-user-id', $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalBehaviors', $result);
        $this->assertArrayHasKey('suspiciousBehaviors', $result);
        $this->assertArrayHasKey('behaviorTypes', $result);
    }

    public function testAnalyzeBehaviorPatternWithEmptyUser(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        $startDate = new \DateTimeImmutable('2023-01-01');
        $endDate = new \DateTimeImmutable('2023-12-31');

        // 测试空用户ID的情况
        $result = $service->analyzeBehaviorPattern('', $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['totalBehaviors']);
    }

    public function testAnalyzeBehaviorPatternDateRange(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        $startDate = new \DateTimeImmutable('2023-01-01');
        $endDate = new \DateTimeImmutable('2023-01-31');

        $result = $service->analyzeBehaviorPattern('test-user-id', $startDate, $endDate);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalBehaviors', $result);
        $this->assertArrayHasKey('suspiciousBehaviors', $result);
        $this->assertArrayHasKey('behaviorTypes', $result);
        $this->assertArrayHasKey('timeDistribution', $result);
        $this->assertArrayHasKey('suspiciousPatterns', $result);
    }

    public function testGenerateBehaviorReport(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        // This would throw exception for non-existent session
        $this->expectException(\Exception::class);
        $service->generateBehaviorReport('non-existent-session');
    }

    public function testGetBehaviorTimeline(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        $result = $service->getBehaviorTimeline('test-session-id');
        $this->assertIsArray($result);
    }

    public function testUpdateSessionStatistics(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        // This would throw exception for non-existent session
        $this->expectException(\Exception::class);
        $service->updateSessionStatistics('non-existent-session');
    }

    public function testDetectSuspiciousBehavior(): void
    {
        $service = self::getService(LearnBehaviorService::class);

        // 创建mock对象
        $behavior = $this->createMock(LearnBehavior::class);
        $session = $this->createMock(LearnSession::class);

        // 设置mock期望
        $behavior->expects($this->once())
            ->method('getBehaviorType')
            ->willReturn(BehaviorType::PLAY)
        ;

        $session->expects($this->once())
            ->method('getId')
            ->willReturn('test-session-id')
        ;

        // 测试detectSuspiciousBehavior方法
        $service->detectSuspiciousBehavior($behavior, $session);

        // 验证方法被调用且不抛出异常，此方法返回void
        $this->expectNotToPerformAssertions();
    }
}
