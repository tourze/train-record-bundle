<?php

namespace Tourze\TrainRecordBundle\Tests\Service\Monitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorDataDisplayer;

/**
 * @internal
 */
#[CoversClass(MonitorDataDisplayer::class)]
#[RunTestsInSeparateProcesses]
final class MonitorDataDisplayerTest extends AbstractIntegrationTestCase
{
    private SymfonyStyle&MockObject $io;

    private MonitorDataDisplayer $displayer;

    protected function onSetUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        // 在集成测试中，应该从容器获取服务实例
        $this->displayer = self::getService(MonitorDataDisplayer::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(MonitorDataDisplayer::class);
        $this->assertInstanceOf(MonitorDataDisplayer::class, $service);
    }

    public function testDisplayMonitoringDataWithJsonFormat(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['activeCount' => 5],
            'anomalies' => ['recentCount' => 2],
            'behaviors' => ['suspiciousCount' => 1],
            'devices' => ['activeCount' => 3],
            'system' => ['status' => 'healthy', 'score' => 95],
        ];

        $this->io->expects($this->once())
            ->method('text')
            ->with(self::callback(static function (mixed $argument) use ($data): bool {
                if (!is_string($argument)) {
                    return false;
                }
                $decoded = json_decode($argument, true);

                return JSON_ERROR_NONE === json_last_error()
                       && is_array($decoded)
                       && isset($decoded['timestamp'])
                       && $decoded['timestamp'] === $data['timestamp'];
            }))
        ;

        $this->displayer->displayMonitoringData($data, 'json', $this->io);
    }

    public function testDisplayMonitoringDataWithJsonFormatHandlesEncodingError(): void
    {
        // Create data with invalid UTF-8 sequences that would cause JSON encoding to fail
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'invalid_data' => "\xB1\x31", // Invalid UTF-8 sequence
        ];

        $this->io->expects($this->once())
            ->method('text')
            ->with('JSON encoding failed')
        ;

        $this->displayer->displayMonitoringData($data, 'json', $this->io);
    }

    public function testDisplayMonitoringDataWithSimpleFormat(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['activeCount' => 5],
            'anomalies' => ['recentCount' => 2],
            'behaviors' => ['suspiciousCount' => 1],
            'devices' => ['activeCount' => 3],
            'system' => ['status' => 'healthy'],
        ];

        $this->io->expects($this->once())
            ->method('text')
            ->with('[2024-01-01 12:00:00] 活跃会话: 5, 异常: 2, 可疑行为: 1, 系统状态: healthy')
        ;

        $this->displayer->displayMonitoringData($data, 'simple', $this->io);
    }

    public function testDisplayMonitoringDataWithDefaultTableFormat(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['activeCount' => 5, 'recentCount' => 10],
            'anomalies' => ['recentCount' => 2, 'unresolvedCount' => 1],
            'behaviors' => ['totalCount' => 20, 'suspiciousCount' => 1, 'suspiciousRate' => 5.0],
            'devices' => ['activeCount' => 3, 'recentCount' => 4],
            'system' => ['status' => 'healthy', 'score' => 95],
        ];

        $this->io->expects($this->once())
            ->method('section')
            ->with('系统监控 - 2024-01-01 12:00:00')
        ;

        $this->io->expects($this->exactly(5))
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <green>HEALTHY</> (健康分数: 95/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $this->displayer->displayMonitoringData($data, 'table', $this->io);
    }

    public function testDisplayMonitoringDataWithUnknownFormatDefaultsToTable(): void
    {
        $data = [
            'timestamp' => '2024-01-01 12:00:00',
            'sessions' => ['activeCount' => 5],
            'anomalies' => ['recentCount' => 2],
            'behaviors' => ['suspiciousCount' => 1],
            'devices' => ['activeCount' => 3],
            'system' => ['status' => 'healthy', 'score' => 95],
        ];

        $this->io->expects($this->once())
            ->method('section')
            ->with('系统监控 - 2024-01-01 12:00:00')
        ;

        $this->displayer->displayMonitoringData($data, 'unknown_format', $this->io);
    }

    public function testExtractSimpleFormatDataHandlesMissingData(): void
    {
        $data = [];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('extractSimpleFormatData');
        $method->setAccessible(true);

        $result = $method->invoke($this->displayer, $data);

        $this->assertEquals([
            'timestamp' => '',
            'sessionsActive' => 0,
            'anomaliesRecent' => 0,
            'behaviorsSuspicious' => 0,
            'systemStatus' => 'unknown',
        ], $result);
    }

    public function testExtractSimpleFormatDataHandlesInvalidDataTypes(): void
    {
        $data = [
            'timestamp' => 12345, // Non-string timestamp
            'sessions' => 'not_array', // Not an array
            'anomalies' => ['recentCount' => 'not_int'], // Non-integer count
            'behaviors' => ['suspiciousCount' => 1.5], // Float count
            'system' => ['status' => ['nested' => 'array']], // Non-scalar status
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('extractSimpleFormatData');
        $method->setAccessible(true);

        $result = $method->invoke($this->displayer, $data);

        $this->assertEquals([
            'timestamp' => '12345',
            'sessionsActive' => 0,
            'anomaliesRecent' => 0,
            'behaviorsSuspicious' => 0,
            'systemStatus' => 'unknown',
        ], $result);
    }

    public function testExtractCountFromData(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('extractCountFromData');
        $method->setAccessible(true);

        // Test with valid integer
        $result = $method->invoke($this->displayer, ['count' => 5], 'count');
        $this->assertEquals(5, $result);

        // Test with missing key
        $result = $method->invoke($this->displayer, ['other' => 5], 'count');
        $this->assertEquals(0, $result);

        // Test with non-integer value
        $result = $method->invoke($this->displayer, ['count' => 'not_int'], 'count');
        $this->assertEquals(0, $result);

        // Test with null value
        $result = $method->invoke($this->displayer, ['count' => null], 'count');
        $this->assertEquals(0, $result);
    }

    public function testDisplaySystemHealthWithHealthyStatus(): void
    {
        $systemData = ['status' => 'healthy', 'score' => 95];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displaySystemHealth');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <green>HEALTHY</> (健康分数: 95/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $method->invoke($this->displayer, $systemData, $this->io);
    }

    public function testDisplaySystemHealthWithWarningStatus(): void
    {
        $systemData = ['status' => 'warning', 'score' => 70];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displaySystemHealth');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <yellow>WARNING</> (健康分数: 70/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $method->invoke($this->displayer, $systemData, $this->io);
    }

    public function testDisplaySystemHealthWithCriticalStatus(): void
    {
        $systemData = ['status' => 'critical', 'score' => 30];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displaySystemHealth');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <red>CRITICAL</> (健康分数: 30/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $method->invoke($this->displayer, $systemData, $this->io);
    }

    public function testDisplaySystemHealthWithUnknownStatus(): void
    {
        $systemData = ['status' => 'unknown', 'score' => 50];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displaySystemHealth');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <white>UNKNOWN</> (健康分数: 50/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $method->invoke($this->displayer, $systemData, $this->io);
    }

    public function testDisplaySystemHealthHandlesMissingData(): void
    {
        $systemData = [];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displaySystemHealth');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('系统状态: <white>UNKNOWN</> (健康分数: 0/100)')
        ;

        $this->io->expects($this->once())
            ->method('newLine')
        ;

        $method->invoke($this->displayer, $systemData, $this->io);
    }

    public function testFormatSuspiciousRate(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('formatSuspiciousRate');
        $method->setAccessible(true);

        // Test with numeric value
        $result = $method->invoke($this->displayer, 25.5);
        $this->assertEquals('25.5%', $result);

        // Test with string numeric value
        $result = $method->invoke($this->displayer, '30');
        $this->assertEquals('30%', $result);

        // Test with non-numeric value
        $result = $method->invoke($this->displayer, 'invalid');
        $this->assertEquals('0%', $result);

        // Test with null value
        $result = $method->invoke($this->displayer, null);
        $this->assertEquals('0%', $result);
    }

    public function testGetHealthColor(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('getHealthColor');
        $method->setAccessible(true);

        $this->assertEquals('green', $method->invoke($this->displayer, 'healthy'));
        $this->assertEquals('yellow', $method->invoke($this->displayer, 'warning'));
        $this->assertEquals('red', $method->invoke($this->displayer, 'critical'));
        $this->assertEquals('white', $method->invoke($this->displayer, 'unknown'));
        $this->assertEquals('white', $method->invoke($this->displayer, 'any_other_status'));
    }

    public function testDisplayMonitoringTablesHandlesEmptyData(): void
    {
        $data = [
            'sessions' => [],
            'anomalies' => [],
            'behaviors' => [],
            'devices' => [],
            'system' => [],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayMonitoringTables');
        $method->setAccessible(true);

        // Should call all table display methods even with empty data
        $this->io->expects($this->exactly(4))
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $this->io->expects($this->once())
            ->method('text')
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayBehaviorsTableWithSuspiciousRate(): void
    {
        $behaviorsData = [
            'totalCount' => 100,
            'suspiciousCount' => 15,
            'suspiciousRate' => 15.5,
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayBehaviorsTable');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['行为指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg)
                        && 3 === count($arg)
                        && is_array($arg[0])
                        && '最近1小时行为' === $arg[0][0]
                        && 100 === $arg[0][1];
                })
            )
        ;

        $method->invoke($this->displayer, $behaviorsData, $this->io);
    }
}
