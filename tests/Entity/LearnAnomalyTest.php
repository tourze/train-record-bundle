<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * @internal
 */
#[CoversClass(LearnAnomaly::class)]
#[RunTestsInSeparateProcesses]
final class LearnAnomalyTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnAnomaly();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $mockSession = new class {
            public function getId(): string
            {
                return 'test-session-id';
            }
        };

        yield 'anomalyType' => ['anomalyType', AnomalyType::MULTIPLE_DEVICE];
        yield 'anomalyDescription' => ['anomalyDescription', '检测到多设备同时登录'];
        yield 'anomalyData' => ['anomalyData', ['ipAddresses' => ['192.168.1.1', '192.168.1.2']]];
        yield 'severity' => ['severity', AnomalySeverity::HIGH];
        yield 'status' => ['status', AnomalyStatus::DETECTED];
        yield 'resolution' => ['resolution', '已验证为正常学习行为'];
        yield 'resolvedBy' => ['resolvedBy', 'admin'];
        yield 'detectTime' => ['detectTime', new \DateTimeImmutable()];
        yield 'resolveTime' => ['resolveTime', new \DateTimeImmutable('+1 hour')];
        yield 'impactScore' => ['impactScore', 7.5];
        yield 'evidence' => ['evidence', ['screenshots' => ['evidence1.png'], 'logs' => ['log1.txt']]];
        yield 'processingNotes' => ['processingNotes', '需要进一步调查'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testIsAutoDetectedProperty(): void
    {
        $entity = new LearnAnomaly();
        $entity->setIsAutoDetected(true);
        $this->assertTrue($entity->isAutoDetected());

        $entity->setIsAutoDetected(false);
        $this->assertFalse($entity->isAutoDetected());
    }

    public function testMarkAsResolved(): void
    {
        $entity = new LearnAnomaly();
        $resolution = '已验证为正常学习行为';
        $resolvedBy = 'admin';
        $entity->markAsResolved($resolution, $resolvedBy);
        $this->assertEquals(AnomalyStatus::RESOLVED, $entity->getStatus());
        $this->assertEquals($resolution, $entity->getResolution());
        $this->assertEquals($resolvedBy, $entity->getResolvedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getResolveTime());
    }

    public function testIsHighPriority(): void
    {
        $entity = new LearnAnomaly();

        // 测试高优先级 - HIGH
        $entity->setSeverity(AnomalySeverity::HIGH);
        $this->assertTrue($entity->isHighPriority());

        // 测试高优先级 - CRITICAL
        $entity->setSeverity(AnomalySeverity::CRITICAL);
        $this->assertTrue($entity->isHighPriority());

        // 测试低优先级 - LOW
        $entity->setSeverity(AnomalySeverity::LOW);
        $this->assertFalse($entity->isHighPriority());

        // 测试中等优先级 - MEDIUM
        $entity->setSeverity(AnomalySeverity::MEDIUM);
        $this->assertFalse($entity->isHighPriority());
    }
}
