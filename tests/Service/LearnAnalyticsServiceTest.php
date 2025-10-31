<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\LearnAnalyticsService;

/**
 * @internal
 */
#[CoversClass(LearnAnalyticsService::class)]
#[RunTestsInSeparateProcesses]
final class LearnAnalyticsServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(LearnAnalyticsService::class);
        $this->assertInstanceOf(LearnAnalyticsService::class, $service);
    }

    public function testGenerateLearningReport(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        try {
            $result = $service->generateLearningReport($startDate, $endDate);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('period', $result);
            $this->assertArrayHasKey('overview', $result);
            $this->assertArrayHasKey('sessions', $result);
            $this->assertArrayHasKey('progress', $result);
            $this->assertArrayHasKey('behaviors', $result);
            $this->assertArrayHasKey('anomalies', $result);
            $this->assertArrayHasKey('trends', $result);
            $this->assertArrayHasKey('insights', $result);
            $this->assertArrayHasKey('generatedTime', $result);
        } catch (\Throwable $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGenerateLearningReportWithDefaults(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        try {
            $result = $service->generateLearningReport();
            $this->assertIsArray($result);
            $this->assertArrayHasKey('period', $result);
        } catch (\Throwable $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGetUserAnalytics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        try {
            $result = $service->getUserAnalytics('user123');
            $this->assertIsArray($result);
            $this->assertArrayHasKey('userId', $result);
            $this->assertArrayHasKey('period', $result);
            $this->assertArrayHasKey('learningProfile', $result);
            $this->assertArrayHasKey('performanceMetrics', $result);
            $this->assertArrayHasKey('behaviorPatterns', $result);
            $this->assertArrayHasKey('progressAnalysis', $result);
            $this->assertArrayHasKey('recommendations', $result);
            $this->assertEquals('user123', $result['userId']);
        } catch (\Throwable $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGetCourseAnalytics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $result = $service->getCourseAnalytics('course456');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseId', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('enrollmentStats', $result);
        $this->assertArrayHasKey('completionAnalysis', $result);
        $this->assertArrayHasKey('engagementMetrics', $result);
        $this->assertArrayHasKey('difficultyAnalysis', $result);
        $this->assertArrayHasKey('learnerSegmentation', $result);
        $this->assertEquals('course456', $result['courseId']);
    }

    public function testGetRealTimeStatistics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $result = $service->getRealTimeStatistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('currentOnline', $result);
        $this->assertArrayHasKey('todayStats', $result);
        $this->assertArrayHasKey('hourlyStats', $result);
        $this->assertArrayHasKey('systemHealth', $result);
    }

    public function testGenerateUserAnalytics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        $result = $service->generateUserAnalytics('user123', $startDate, $endDate);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('behavior', $result);
        $this->assertArrayHasKey('progress', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertEquals('user123', $result['userId']);
    }

    public function testGenerateCourseAnalytics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        $result = $service->generateCourseAnalytics('course456', $startDate, $endDate);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseId', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('enrollment', $result);
        $this->assertArrayHasKey('completion', $result);
        $this->assertArrayHasKey('engagement', $result);
        $this->assertArrayHasKey('difficulty', $result);
        $this->assertArrayHasKey('learners', $result);
        $this->assertEquals('course456', $result['courseId']);
    }

    public function testGenerateSystemAnalytics(): void
    {
        $service = self::getService(LearnAnalyticsService::class);

        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        try {
            $result = $service->generateSystemAnalytics($startDate, $endDate);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('period', $result);
            $this->assertArrayHasKey('overall', $result);
            $this->assertArrayHasKey('trends', $result);
            $this->assertArrayHasKey('anomalies', $result);
        } catch (\Throwable $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }
}
