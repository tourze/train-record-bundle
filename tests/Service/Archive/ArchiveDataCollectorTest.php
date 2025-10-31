<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Service\Archive;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveDataCollector;

/**
 * ArchiveDataCollector 集成测试
 *
 * @internal
 */
#[CoversClass(ArchiveDataCollector::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveDataCollectorTest extends AbstractIntegrationTestCase
{
    private ArchiveDataCollector $dataCollector;

    protected function onSetUp(): void
    {
        $container = self::getContainer();
        $dataCollector = $container->get(ArchiveDataCollector::class);
        $this->assertInstanceOf(ArchiveDataCollector::class, $dataCollector);
        $this->dataCollector = $dataCollector;
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertNotNull($this->dataCollector);
    }

    public function testCollectLearningDataWithEmptyData(): void
    {
        $userId = 'user123';
        $courseId = 'course456';

        $result = $this->dataCollector->collectLearningData($userId, $courseId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sessionSummary', $result);
        $this->assertArrayHasKey('behaviorSummary', $result);
        $this->assertArrayHasKey('anomalySummary', $result);
        $this->assertArrayHasKey('totalEffectiveTime', $result);
        $this->assertArrayHasKey('totalSessions', $result);
        $this->assertArrayHasKey('archiveGeneratedAt', $result);

        // 验证数据结构
        $this->assertEquals(0, $result['totalSessions']);
        $this->assertEquals(0.0, $result['totalEffectiveTime']);
        $this->assertIsArray($result['sessionSummary']);
        $this->assertIsArray($result['behaviorSummary']);
        $this->assertIsArray($result['anomalySummary']);

        // 验证时间戳格式
        $archiveGeneratedAt = $result['archiveGeneratedAt'];
        $this->assertIsString($archiveGeneratedAt);
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
            $archiveGeneratedAt
        );
    }

    public function testCollectLearningDataSessionSummaryStructure(): void
    {
        $result = $this->dataCollector->collectLearningData('user123', 'course456');

        $sessionSummary = $result['sessionSummary'];
        $this->assertIsArray($sessionSummary);
        $this->assertArrayHasKey('totalSessions', $sessionSummary);
        $this->assertArrayHasKey('totalTime', $sessionSummary);
        $this->assertArrayHasKey('completionRate', $sessionSummary);
        $this->assertArrayHasKey('averageSessionTime', $sessionSummary);
        $this->assertArrayHasKey('firstLearnTime', $sessionSummary);
        $this->assertArrayHasKey('lastLearnTime', $sessionSummary);

        // 验证数据类型
        $this->assertIsInt($sessionSummary['totalSessions']);
        $this->assertIsNumeric($sessionSummary['totalTime']);
        $this->assertIsNumeric($sessionSummary['completionRate']);
        $this->assertIsNumeric($sessionSummary['averageSessionTime']);
    }

    public function testCollectLearningDataBehaviorSummaryStructure(): void
    {
        $result = $this->dataCollector->collectLearningData('user123', 'course456');

        $behaviorSummary = $result['behaviorSummary'];
        $this->assertIsArray($behaviorSummary);
        $this->assertArrayHasKey('totalBehaviors', $behaviorSummary);
        $this->assertArrayHasKey('behaviorStats', $behaviorSummary);
        $this->assertArrayHasKey('suspiciousCount', $behaviorSummary);
        $this->assertArrayHasKey('mostCommonBehavior', $behaviorSummary);

        // 验证数据类型
        $this->assertIsInt($behaviorSummary['totalBehaviors']);
        $this->assertIsArray($behaviorSummary['behaviorStats']);
        $this->assertIsInt($behaviorSummary['suspiciousCount']);
    }

    public function testCollectLearningDataAnomalySummaryStructure(): void
    {
        $result = $this->dataCollector->collectLearningData('user123', 'course456');

        $anomalySummary = $result['anomalySummary'];
        $this->assertIsArray($anomalySummary);
        $this->assertArrayHasKey('totalAnomalies', $anomalySummary);
        $this->assertArrayHasKey('anomalyTypes', $anomalySummary);
        $this->assertArrayHasKey('resolutionStats', $anomalySummary);
        $this->assertArrayHasKey('severityDistribution', $anomalySummary);

        // 验证数据类型
        $this->assertIsInt($anomalySummary['totalAnomalies']);
        $this->assertIsArray($anomalySummary['anomalyTypes']);
        $this->assertIsArray($anomalySummary['resolutionStats']);
        $this->assertIsArray($anomalySummary['severityDistribution']);

        // 验证解决状态统计结构
        $resolutionStats = $anomalySummary['resolutionStats'];
        $this->assertIsArray($resolutionStats);
        $this->assertArrayHasKey('resolved', $resolutionStats);
        $this->assertArrayHasKey('pending', $resolutionStats);
        $this->assertArrayHasKey('ignored', $resolutionStats);
    }
}
