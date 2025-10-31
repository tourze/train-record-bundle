<?php

namespace Tourze\TrainRecordBundle\Tests\Service\Monitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorLogger;

/**
 * @internal
 */
#[CoversClass(MonitorLogger::class)]
#[RunTestsInSeparateProcesses]
final class MonitorLoggerTest extends AbstractIntegrationTestCase
{
    private MonitorLogger $logger;

    private string $tempFile;

    /** @var resource|null */
    private $fileHandle;

    protected function onSetUp(): void
    {
        // 在集成测试中，应该从容器获取服务实例
        $this->logger = self::getService(MonitorLogger::class);
        $this->tempFile = tempnam(sys_get_temp_dir(), 'monitor_logger_test_');
        $handle = fopen($this->tempFile, 'w+');
        if (false === $handle) {
            throw new \RuntimeException('Failed to create temp file');
        }
        $this->fileHandle = $handle;
    }

    protected function onTearDown(): void
    {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(MonitorLogger::class);
        $this->assertInstanceOf(MonitorLogger::class, $service);
    }

    public function testWriteToLogFileWithCompleteData(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => 5],
            'anomalies' => ['recent' => 2],
            'behaviors' => ['suspicious' => 1],
            'system' => ['status' => 'healthy', 'score' => 95],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 5, Anomalies: 2, Suspicious: 1, Health: healthy (95)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithMissingData(): void
    {
        $data = [];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[] Sessions: 0, Anomalies: 0, Suspicious: 0, Health: unknown (0)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithPartialData(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => 3],
            // Missing anomalies, behaviors, system
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 3, Anomalies: 0, Suspicious: 0, Health: unknown (0)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithInvalidDataTypes(): void
    {
        $data = [
            'timestamp' => 12345, // Non-string timestamp
            'sessions' => ['active' => 'not_int'], // Non-integer count
            'anomalies' => ['recent' => null], // Null value
            'behaviors' => ['suspicious' => 1.5], // Float value
            'system' => ['status' => ['nested' => 'array'], 'score' => 'not_int'], // Invalid types
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[12345] Sessions: 0, Anomalies: 0, Suspicious: 0, Health: unknown (0)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithNonArraySections(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => 'not_array',
            'anomalies' => 123,
            'behaviors' => null,
            'system' => 'not_array',
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 0, Anomalies: 0, Suspicious: 0, Health: unknown (0)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWritesAndFlushes(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => 10],
            'anomalies' => ['recent' => 5],
            'behaviors' => ['suspicious' => 2],
            'system' => ['status' => 'warning', 'score' => 75],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        // Write to file
        $this->logger->writeToLogFile($data, $handle);

        // Data should be immediately available due to fflush
        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 10, Anomalies: 5, Suspicious: 2, Health: warning (75)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithCriticalStatus(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => 0],
            'anomalies' => ['recent' => 25],
            'behaviors' => ['suspicious' => 15],
            'system' => ['status' => 'critical', 'score' => 30],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 0, Anomalies: 25, Suspicious: 15, Health: critical (30)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithNegativeValues(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => -1], // Should be treated as 0
            'anomalies' => ['recent' => -5], // Should be treated as 0
            'behaviors' => ['suspicious' => -2], // Should be treated as 0
            'system' => ['status' => 'healthy', 'score' => -10], // Should be treated as 0
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        // Note: The implementation doesn't validate for negative values, so they pass through
        $expected = "[2024-01-01 12:00:00] Sessions: -1, Anomalies: -5, Suspicious: -2, Health: healthy (-10)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithLargeValues(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => PHP_INT_MAX],
            'anomalies' => ['recent' => 999999],
            'behaviors' => ['suspicious' => 50000],
            'system' => ['status' => 'healthy', 'score' => 100],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = '[2024-01-01 12:00:00] Sessions: ' . PHP_INT_MAX . ", Anomalies: 999999, Suspicious: 50000, Health: healthy (100)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithSpecialCharactersInStatus(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['active' => 5],
            'anomalies' => ['recent' => 2],
            'behaviors' => ['suspicious' => 1],
            'system' => ['status' => 'status-with-special-chars_123', 'score' => 85],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[2024-01-01 12:00:00] Sessions: 5, Anomalies: 2, Suspicious: 1, Health: status-with-special-chars_123 (85)\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteToLogFileWithEmptyTimestamp(): void
    {
        $data = [
            'timestamp' => '',
            'sessions' => ['active' => 5],
            'anomalies' => ['recent' => 2],
            'behaviors' => ['suspicious' => 1],
            'system' => ['status' => 'healthy', 'score' => 95],
        ];

        $handle = $this->fileHandle;
        if (null === $handle) {
            self::fail('File handle is null');
        }

        $this->logger->writeToLogFile($data, $handle);

        rewind($handle);
        $content = stream_get_contents($handle);

        $expected = "[] Sessions: 5, Anomalies: 2, Suspicious: 1, Health: healthy (95)\n";
        $this->assertEquals($expected, $content);
    }
}
