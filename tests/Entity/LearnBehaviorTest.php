<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

/**
 * @internal
 */
#[CoversClass(LearnBehavior::class)]
#[RunTestsInSeparateProcesses]
final class LearnBehaviorTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnBehavior();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'behaviorType' => ['behaviorType', BehaviorType::PLAY];
        yield 'behaviorData' => ['behaviorData', ['action' => 'play', 'timestamp' => '2024-01-01 10:00:00', 'duration' => 30]];
        yield 'videoTimestamp' => ['videoTimestamp', '123.456'];
        yield 'deviceFingerprint' => ['deviceFingerprint', 'abc123def456'];
        yield 'ipAddress' => ['ipAddress', '192.168.1.100'];
        yield 'suspiciousReason' => ['suspiciousReason', '检测到快速跳转'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
    }

    public function testSessionProperty(): void
    {
        $entity = new LearnBehavior();
        $session = new LearnSession();
        $entity->setSession($session);
        $this->assertSame($session, $entity->getSession());
    }

    public function testBehaviorTypeProperty(): void
    {
        $entity = new LearnBehavior();
        $behaviorType = BehaviorType::PLAY;
        $entity->setBehaviorType($behaviorType);
        $this->assertEquals($behaviorType, $entity->getBehaviorType());
    }

    public function testBehaviorDataProperty(): void
    {
        $entity = new LearnBehavior();
        $behaviorData = ['action' => 'play', 'timestamp' => '2024-01-01 10:00:00', 'duration' => 30];
        $entity->setBehaviorData($behaviorData);
        $this->assertEquals($behaviorData, $entity->getBehaviorData());
    }

    public function testVideoTimestampProperty(): void
    {
        $entity = new LearnBehavior();
        $videoTimestamp = '123.456';
        $entity->setVideoTimestamp($videoTimestamp);
        $this->assertEquals($videoTimestamp, $entity->getVideoTimestamp());
    }

    public function testDeviceFingerprintProperty(): void
    {
        $entity = new LearnBehavior();
        $fingerprint = 'abc123def456';
        $entity->setDeviceFingerprint($fingerprint);
        $this->assertEquals($fingerprint, $entity->getDeviceFingerprint());
    }

    public function testIpAddressProperty(): void
    {
        $entity = new LearnBehavior();
        $ipAddress = '192.168.1.100';
        $entity->setIpAddress($ipAddress);
        $this->assertEquals($ipAddress, $entity->getIpAddress());
    }

    public function testIsSuspiciousProperty(): void
    {
        $entity = new LearnBehavior();
        // 测试默认值
        $this->assertFalse($entity->isSuspicious());
        // 测试设置新值
        $entity->setIsSuspicious(true);
        $entity->setSuspiciousReason('检测到快速跳转');
        $this->assertTrue($entity->isSuspicious());
        $this->assertEquals('检测到快速跳转', $entity->getSuspiciousReason());
    }

    public function testSetBehaviorTypeWithSuspiciousBehavior(): void
    {
        $entity = new LearnBehavior();
        // 使用实际的可疑行为类型
        $suspiciousBehaviorType = BehaviorType::DEVELOPER_TOOLS;
        $entity->setBehaviorType($suspiciousBehaviorType);
        $suspiciousReason = $entity->getSuspiciousReason();
        $this->assertNotNull($suspiciousReason);
        $this->assertStringContainsString('系统自动检测', $suspiciousReason);
    }

    public function testIsFocusRelated(): void
    {
        $entity = new LearnBehavior();
        // 测试焦点相关行为
        $entity->setBehaviorType(BehaviorType::WINDOW_FOCUS);
        $this->assertTrue($entity->isFocusRelated());
        $entity->setBehaviorType(BehaviorType::WINDOW_BLUR);
        $this->assertTrue($entity->isFocusRelated());
        // 测试非焦点相关行为
        $entity->setBehaviorType(BehaviorType::PLAY);
        $this->assertFalse($entity->isFocusRelated());
    }

    public function testGetBehaviorCategory(): void
    {
        $entity = new LearnBehavior();
        $entity->setBehaviorType(BehaviorType::PLAY);
        $this->assertEquals('video_control', $entity->getBehaviorCategory());
    }

    public function testIsVideoControl(): void
    {
        $entity = new LearnBehavior();
        // 使用视频控制类型的行为
        $videoControlType = BehaviorType::PLAY;
        $entity->setBehaviorType($videoControlType);
        $this->assertTrue($entity->isVideoControl());
    }
}
