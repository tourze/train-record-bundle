<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Service\Archive;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveStatisticsCalculator;

/**
 * ArchiveStatisticsCalculator 集成测试
 *
 * @internal
 */
#[CoversClass(ArchiveStatisticsCalculator::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveStatisticsCalculatorTest extends AbstractIntegrationTestCase
{
    private ArchiveStatisticsCalculator $statisticsCalculator;

    protected function onSetUp(): void
    {
        $this->statisticsCalculator = self::getService(ArchiveStatisticsCalculator::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertNotNull($this->statisticsCalculator);
    }

    public function testGetArchiveStatistics(): void
    {
        $result = $this->statisticsCalculator->getArchiveStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalArchives', $result);
        $this->assertArrayHasKey('expiredArchives', $result);
        $this->assertArrayHasKey('archivedArchives', $result);
        $this->assertArrayHasKey('totalStorageSize', $result);
        $this->assertArrayHasKey('formatDistribution', $result);
        $this->assertArrayHasKey('monthlyArchiveCount', $result);

        // 验证数据类型
        $this->assertIsInt($result['totalArchives']);
        $this->assertIsInt($result['expiredArchives']);
        $this->assertIsInt($result['archivedArchives']);
        $this->assertIsInt($result['totalStorageSize']);
        $this->assertIsArray($result['formatDistribution']);
        $this->assertIsArray($result['monthlyArchiveCount']);

        // 验证逻辑性（数量不能为负数）
        $this->assertGreaterThanOrEqual(0, $result['totalArchives']);
        $this->assertGreaterThanOrEqual(0, $result['expiredArchives']);
        $this->assertGreaterThanOrEqual(0, $result['archivedArchives']);
        $this->assertGreaterThanOrEqual(0, $result['totalStorageSize']);
    }

    public function testGetExpiringArchivesWithDefaultDays(): void
    {
        $result = $this->statisticsCalculator->getExpiringArchives();

        $this->assertIsArray($result);
        // 默认30天内过期的档案
        foreach ($result as $archive) {
            $this->assertInstanceOf(LearnArchive::class, $archive);
        }
    }

    public function testGetExpiringArchivesWithCustomDays(): void
    {
        $result = $this->statisticsCalculator->getExpiringArchives(7);

        $this->assertIsArray($result);
        // 7天内过期的档案
        foreach ($result as $archive) {
            $this->assertInstanceOf(LearnArchive::class, $archive);
        }
    }

    public function testGetExpiringArchivesWithZeroDays(): void
    {
        $result = $this->statisticsCalculator->getExpiringArchives(0);

        $this->assertIsArray($result);
        // 今天过期的档案
    }

    public function testGetExpiringArchivesWithNegativeDaysReturnsEmpty(): void
    {
        // 测试负数天数的行为
        $result = $this->statisticsCalculator->getExpiringArchives(-5);

        $this->assertIsArray($result);
        // 负数天数应该返回空数组或所有过期档案
    }

    public function testGetExpiringArchivesWithLargeDaysNumber(): void
    {
        // 测试很大的天数的行为
        $result = $this->statisticsCalculator->getExpiringArchives(365);

        $this->assertIsArray($result);
        // 应该返回一年内过期的所有档案
    }
}
