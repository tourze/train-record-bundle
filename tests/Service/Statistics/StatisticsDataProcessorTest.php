<?php

namespace Tourze\TrainRecordBundle\Tests\Service\Statistics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataProcessor;

/**
 * @internal
 */
#[CoversClass(StatisticsDataProcessor::class)]
#[RunTestsInSeparateProcesses]
final class StatisticsDataProcessorTest extends AbstractIntegrationTestCase
{
    private SymfonyStyle&MockObject $io;

    private StatisticsDataProcessor $processor;

    private string $tempDir;

    protected function onSetUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        // 在集成测试中，应该从容器获取服务实例
        $this->processor = self::getService(StatisticsDataProcessor::class);
        $this->tempDir = sys_get_temp_dir() . '/statistics_processor_test_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
    }

    protected function onTearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(StatisticsDataProcessor::class);
        $this->assertInstanceOf(StatisticsDataProcessor::class, $service);
    }

    public function testSaveStatistics(): void
    {
        $type = StatisticsType::USER;
        $period = StatisticsPeriod::DAILY;
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'activeUsers' => 50,
            ],
        ];
        $scopeId = 'user123';

        // This method currently has a TODO implementation, so it should not throw any exceptions
        $this->expectNotToPerformAssertions();

        $this->processor->saveStatistics($type, $period, $data, $scopeId);
    }

    public function testExportStatisticsWithJsonFormat(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'activeUsers' => 50,
                'completionRate' => 0.75,
            ],
            'userMetrics' => [
                'averageScore' => 85.5,
                'studyTime' => 120,
            ],
        ];
        $filePath = $this->tempDir . '/test_export.json';
        $format = 'json';

        $this->processor->exportStatistics($data, $filePath, $format);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $decodedData = json_decode($content, true);
        $this->assertIsArray($decodedData);
        $this->assertEquals($data, $decodedData);
    }

    public function testExportStatisticsWithCsvFormat(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'activeUsers' => 50,
            ],
            'userMetrics' => [
                'averageScore' => 85.5,
            ],
        ];
        $filePath = $this->tempDir . '/test_export.csv';
        $format = 'csv';

        $this->processor->exportStatistics($data, $filePath, $format);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(3, count($lines)); // Header + at least 2 data lines
        $this->assertEquals('指标,数值', $lines[0]); // Header
        $this->assertStringContainsString('overview.totalUsers,100', $content);
        $this->assertStringContainsString('overview.activeUsers,50', $content);
        $this->assertStringContainsString('userMetrics.averageScore,85.5', $content);
    }

    public function testExportStatisticsWithUnsupportedFormat(): void
    {
        $data = ['test' => 'data'];
        $filePath = $this->tempDir . '/test_export.txt';
        $format = 'unsupported';

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('不支持的导出格式: unsupported');

        $this->processor->exportStatistics($data, $filePath, $format);
    }

    public function testExportStatisticsCreatesDirectory(): void
    {
        $data = ['test' => 'data'];
        $nestedDir = $this->tempDir . '/nested/directory';
        $filePath = $nestedDir . '/test_export.json';
        $format = 'json';

        $this->assertDirectoryDoesNotExist($nestedDir);

        $this->processor->exportStatistics($data, $filePath, $format);

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($filePath);
    }

    public function testExportJsonFormatHandlesEncodingError(): void
    {
        // Create data with invalid UTF-8 sequences
        $data = [
            'invalid_data' => "\xB1\x31", // Invalid UTF-8 sequence
        ];
        $filePath = $this->tempDir . '/test_export.json';

        $this->processor->exportStatistics($data, $filePath, 'json');

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertEquals('{}', $content);
    }

    public function testExportCsvFormatWithNestedData(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'details' => [
                    'activeUsers' => 50,
                    'demographics' => [
                        'ageGroups' => ['18-25' => 30, '26-35' => 40],
                    ],
                ],
            ],
        ];
        $filePath = $this->tempDir . '/nested_export.csv';

        $this->processor->exportStatistics($data, $filePath, 'csv');

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString('overview.totalUsers,100', $content);
        $this->assertStringContainsString('overview.details.activeUsers,50', $content);
        $this->assertStringContainsString('overview.details.demographics.ageGroups', $content);
    }

    public function testExportCsvFormatHandlesFileOpenFailure(): void
    {
        $data = ['test' => 'data'];
        // Use a directory path instead of a file path to cause fopen to fail
        $filePath = $this->tempDir; // This is a directory, not a file

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('Failed to open file for writing: ' . $filePath);

        $this->processor->exportStatistics($data, $filePath, 'csv');
    }

    public function testHandleStatisticsPersistenceWithSaveEnabled(): void
    {
        $statisticsType = StatisticsType::USER;
        $statisticsPeriod = StatisticsPeriod::DAILY;
        $statisticsData = [
            'overview' => [
                'totalUsers' => 100,
            ],
        ];
        $userId = 'user123';
        $courseId = null;
        $save = true;

        $this->io->expects($this->once())
            ->method('note')
            ->with('统计数据已保存到数据库')
        ;

        $this->processor->handleStatisticsPersistence(
            $statisticsType,
            $statisticsPeriod,
            $statisticsData,
            $userId,
            $courseId,
            $save,
            $this->io
        );
    }

    public function testHandleStatisticsPersistenceWithSaveDisabled(): void
    {
        $statisticsType = StatisticsType::USER;
        $statisticsPeriod = StatisticsPeriod::DAILY;
        $statisticsData = [
            'overview' => [
                'totalUsers' => 100,
            ],
        ];
        $userId = 'user123';
        $courseId = null;
        $save = false;

        $this->io->expects($this->never())->method('note');

        $this->processor->handleStatisticsPersistence(
            $statisticsType,
            $statisticsPeriod,
            $statisticsData,
            $userId,
            $courseId,
            $save,
            $this->io
        );
    }

    public function testHandleStatisticsPersistenceWithCourseId(): void
    {
        $statisticsType = StatisticsType::COURSE;
        $statisticsPeriod = StatisticsPeriod::WEEKLY;
        $statisticsData = [
            'overview' => [
                'totalCourses' => 25,
            ],
        ];
        $userId = null;
        $courseId = 'course123';
        $save = true;

        $this->io->expects($this->once())
            ->method('note')
            ->with('统计数据已保存到数据库')
        ;

        $this->processor->handleStatisticsPersistence(
            $statisticsType,
            $statisticsPeriod,
            $statisticsData,
            $userId,
            $courseId,
            $save,
            $this->io
        );
    }

    public function testHandleStatisticsPersistenceWithGlobalScope(): void
    {
        $statisticsType = StatisticsType::SYSTEM;
        $statisticsPeriod = StatisticsPeriod::MONTHLY;
        $statisticsData = [
            'overview' => [
                'systemHealth' => 95,
            ],
        ];
        $userId = null;
        $courseId = null;
        $save = true;

        $this->io->expects($this->once())
            ->method('note')
            ->with('统计数据已保存到数据库')
        ;

        $this->processor->handleStatisticsPersistence(
            $statisticsType,
            $statisticsPeriod,
            $statisticsData,
            $userId,
            $courseId,
            $save,
            $this->io
        );
    }

    public function testHandleStatisticsExportWithExportPath(): void
    {
        $statisticsData = [
            'overview' => [
                'totalUsers' => 100,
            ],
        ];
        $exportPath = $this->tempDir . '/export_test.json';
        $format = 'json';

        $this->io->expects($this->once())
            ->method('note')
            ->with("统计数据已导出到: {$exportPath}")
        ;

        $this->processor->handleStatisticsExport(
            $statisticsData,
            $exportPath,
            $format,
            $this->io
        );

        $this->assertFileExists($exportPath);
    }

    public function testHandleStatisticsExportWithNullExportPath(): void
    {
        $statisticsData = [
            'overview' => [
                'totalUsers' => 100,
            ],
        ];
        $exportPath = null;
        $format = 'json';

        $this->io->expects($this->never())->method('note');

        $this->processor->handleStatisticsExport(
            $statisticsData,
            $exportPath,
            $format,
            $this->io
        );
    }

    public function testHandleStatisticsExportWithCsvFormat(): void
    {
        $statisticsData = [
            'overview' => [
                'totalUsers' => 100,
                'activeUsers' => 50,
            ],
        ];
        $exportPath = $this->tempDir . '/export_test.csv';
        $format = 'csv';

        $this->io->expects($this->once())
            ->method('note')
            ->with("统计数据已导出到: {$exportPath}")
        ;

        $this->processor->handleStatisticsExport(
            $statisticsData,
            $exportPath,
            $format,
            $this->io
        );

        $this->assertFileExists($exportPath);
        $content = file_get_contents($exportPath);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('overview.totalUsers,100', $content);
    }

    public function testWriteCsvDataWithComplexNestedStructure(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                    'array' => ['item1', 'item2'],
                ],
                'simple' => 'simple_value',
            ],
        ];
        $filePath = $this->tempDir . '/complex.csv';

        $this->processor->exportStatistics($data, $filePath, 'csv');

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('level1.level2.level3,deep_value', $content);
        $this->assertStringContainsString('level1.level2.simple,simple_value', $content);
    }

    public function testWriteCsvDataWithMixedValueTypes(): void
    {
        $data = [
            'string_value' => 'test_string',
            'int_value' => 42,
            'float_value' => 3.14159,
            'bool_value' => true,
            'null_value' => null,
            'array_value' => ['nested' => 'data'],
        ];
        $filePath = $this->tempDir . '/mixed_types.csv';

        $this->processor->exportStatistics($data, $filePath, 'csv');

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('string_value,test_string', $content);
        $this->assertStringContainsString('int_value,42', $content);
        $this->assertStringContainsString('float_value,3.14', $content);
        $this->assertStringContainsString('bool_value,1', $content);
        $this->assertStringContainsString('null_value,', $content);
    }

    public function testExportStatisticsWithEmptyData(): void
    {
        $data = [];
        $filePath = $this->tempDir . '/empty.json';
        $format = 'json';

        $this->processor->exportStatistics($data, $filePath, 'format');

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertEquals('[]', $content);
    }

    public function testExportStatisticsWithLargeData(): void
    {
        $data = [];
        for ($i = 0; $i < 1000; ++$i) {
            $data["item_{$i}"] = [
                'value' => $i,
                'description' => "Item number {$i}",
                'metadata' => [
                    'created' => time(),
                    'type' => 'test',
                ],
            ];
        }
        $filePath = $this->tempDir . '/large.json';
        $format = 'json';

        $this->processor->exportStatistics($data, $filePath, $format);

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $decodedData = json_decode($content, true);
        $this->assertIsArray($decodedData);
        $this->assertCount(1000, $decodedData);
        $this->assertEquals($data, $decodedData);
    }
}
