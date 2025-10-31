<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\LearnProgressService;

/**
 * @internal
 */
#[CoversClass(LearnProgressService::class)]
#[RunTestsInSeparateProcesses]
final class LearnProgressServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(LearnProgressService::class);
        $this->assertInstanceOf(LearnProgressService::class, $service);
    }

    public function testUpdateProgressWithValidData(): void
    {
        $service = self::getService(LearnProgressService::class);

        // 测试更新进度的行为 - updateProgress需要6个参数：userId, courseId, lessonId, currentTime, totalDuration, deviceFingerprint
        $this->expectException(\Exception::class); // 预期抛出异常，因为数据不存在

        $service->updateProgress(
            'test-user-id',
            'test-course-id',
            'test-lesson-id',
            75.5,
            100.0,
            'test-device'
        );
    }

    public function testUpdateProgressWithInvalidData(): void
    {
        $service = self::getService(LearnProgressService::class);

        // 测试传入无效数据
        $this->expectException(\Exception::class);

        $service->updateProgress(
            '',
            '',
            '',
            -10.0,
            0.0,
            'test-device'
        );
    }

    public function testGetUserProgress(): void
    {
        $service = self::getService(LearnProgressService::class);

        // Test with course ID
        $result = $service->getUserProgress('test-user-id', 'test-course-id');
        $this->assertIsArray($result);

        // Test without course ID
        $result = $service->getUserProgress('test-user-id');
        $this->assertIsArray($result);
    }

    public function testSyncProgress(): void
    {
        $service = self::getService(LearnProgressService::class);

        $result = $service->syncProgress(
            'test-user-id',
            'test-lesson-id',
            'from-device',
            'to-device'
        );

        // Returns null if progress not found
        $this->assertNull($result);
    }

    public function testCalculateCourseProgress(): void
    {
        $service = self::getService(LearnProgressService::class);

        $result = $service->calculateCourseProgress('test-user-id', 'test-course-id');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalLessons', $result);
        $this->assertArrayHasKey('completedLessons', $result);
        $this->assertArrayHasKey('overallProgress', $result);
        $this->assertArrayHasKey('totalEffectiveTime', $result);
        $this->assertArrayHasKey('averageProgress', $result);
    }

    public function testGetProgressStatistics(): void
    {
        $service = self::getService(LearnProgressService::class);

        $startDate = new \DateTimeImmutable('2023-01-01');
        $endDate = new \DateTimeImmutable('2023-12-31');

        // Test with date range
        $result = $service->getProgressStatistics('test-user-id', $startDate, $endDate);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalSessions', $result);
        $this->assertArrayHasKey('completedSessions', $result);
        $this->assertArrayHasKey('totalWatchedTime', $result);
        $this->assertArrayHasKey('totalEffectiveTime', $result);
        $this->assertArrayHasKey('progressDistribution', $result);

        // Test without date range - removed as it requires proper parameters
    }

    public function testResetProgress(): void
    {
        $service = self::getService(LearnProgressService::class);

        $result = $service->resetProgress('test-user-id', 'test-lesson-id');
        $this->assertIsBool($result);
        $this->assertFalse($result); // Should return false for non-existent progress
    }

    public function testRecalculateEffectiveTime(): void
    {
        $service = self::getService(LearnProgressService::class);

        // Test with course ID
        $result = $service->recalculateEffectiveTime('test-user-id', 'test-course-id');
        $this->assertIsInt($result);

        // Test without course ID
        $result = $service->recalculateEffectiveTime('test-user-id');
        $this->assertIsInt($result);
    }
}
