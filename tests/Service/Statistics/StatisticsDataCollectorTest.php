<?php

namespace Tourze\TrainRecordBundle\Tests\Service\Statistics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataCollector;

/**
 * @internal
 */
#[CoversClass(StatisticsDataCollector::class)]
#[RunTestsInSeparateProcesses]
final class StatisticsDataCollectorTest extends AbstractIntegrationTestCase
{
    // 集成测试：使用真实服务进行测试

    protected function onSetUp(): void
    {
        // 集成测试不需要额外的设置
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(StatisticsDataCollector::class);
        $this->assertInstanceOf(StatisticsDataCollector::class, $service);
    }

    public function testGenerateStatisticsDataForUserType(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $userId = 'user123';

        $collector = self::getService(StatisticsDataCollector::class);
        $result = $collector->generateStatisticsData(
            StatisticsType::USER,
            $startDate,
            $endDate,
            $userId
        );

        // 验证返回的数据结构
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertEquals($userId, $result['userId']);
    }

    public function testGenerateStatisticsDataForCourseType(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $courseId = 'course123';

        $collector = self::getService(StatisticsDataCollector::class);
        $result = $collector->generateStatisticsData(
            StatisticsType::COURSE,
            $startDate,
            $endDate,
            null,
            $courseId
        );

        // 验证返回的数据结构
        $this->assertIsArray($result);
        $this->assertArrayHasKey('courseId', $result);
        $this->assertEquals($courseId, $result['courseId']);
    }

    public function testGenerateStatisticsDataWithoutIds(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $collector = self::getService(StatisticsDataCollector::class);

        // 测试所有统计类型都能返回有效数组
        $types = [
            StatisticsType::USER,
            StatisticsType::COURSE,
            StatisticsType::BEHAVIOR,
            StatisticsType::ANOMALY,
            StatisticsType::DEVICE,
            StatisticsType::PROGRESS,
            StatisticsType::DURATION,
        ];

        foreach ($types as $type) {
            $result = $collector->generateStatisticsData($type, $startDate, $endDate);
            $this->assertIsArray($result);
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('overview', $result);
        }
    }

    public function testGenerateStatisticsDataForSystemType(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $collector = self::getService(StatisticsDataCollector::class);
        $result = $collector->generateStatisticsData(
            StatisticsType::SYSTEM,
            $startDate,
            $endDate
        );

        // 验证返回的数据结构
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
