<?php

namespace Tourze\TrainRecordBundle\Tests\Service\Statistics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataDisplayer;

/**
 * @internal
 */
#[CoversClass(StatisticsDataDisplayer::class)]
#[RunTestsInSeparateProcesses]
final class StatisticsDataDisplayerTest extends AbstractIntegrationTestCase
{
    private SymfonyStyle&MockObject $io;

    private StatisticsDataDisplayer $displayer;

    protected function onSetUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        // 在集成测试中，应该从容器获取服务实例
        $this->displayer = self::getService(StatisticsDataDisplayer::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(StatisticsDataDisplayer::class);
        $this->assertInstanceOf(StatisticsDataDisplayer::class, $service);
    }

    public function testDisplayStatisticsWithJsonFormat(): void
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

        $this->io->expects($this->once())
            ->method('text')
            ->with(self::callback(function ($argument): bool {
                if (!is_string($argument)) {
                    return false;
                }
                $decoded = json_decode($argument, true);
                if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
                    return false;
                }

                return isset($decoded['overview'])
                    && is_array($decoded['overview'])
                    && isset($decoded['overview']['totalUsers'])
                    && 100 === $decoded['overview']['totalUsers'];
            }))
        ;

        $this->displayer->displayStatistics($data, 'json', $this->io);
    }

    public function testDisplayStatisticsWithJsonFormatHandlesEncodingError(): void
    {
        // Create data with invalid UTF-8 sequences that would cause JSON encoding to fail
        $data = [
            'invalid_data' => "\xB1\x31", // Invalid UTF-8 sequence
        ];

        $this->io->expects($this->once())
            ->method('text')
            ->with('JSON encoding failed')
        ;

        $this->displayer->displayStatistics($data, 'json', $this->io);
    }

    public function testDisplayStatisticsWithTableFormat(): void
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
            'courseMetrics' => [
                'totalCourses' => 25,
                'activeCourses' => 15,
            ],
            'behaviorAnalysis' => [
                'suspiciousBehaviors' => 5,
                'totalBehaviors' => 1000,
            ],
        ];

        // 设置连续的 section 调用期望
        $this->io->expects($this->exactly(4))
            ->method('section')
            ->with(self::callback(static function (mixed $title): bool {
                $expectedTitles = ['概览', '用户指标', '课程指标', '行为分析'];

                return is_string($title) && in_array($title, $expectedTitles, true);
            }))
        ;

        // 设置连续的 table 调用期望
        $this->io->expects($this->exactly(4))
            ->method('table')
            ->with(
                $this->equalTo(['指标', '数值']),
                self::callback(function ($data) {
                    return is_array($data);
                })
            )
        ;

        $this->displayer->displayStatistics($data, 'table', $this->io);
    }

    public function testDisplayStatisticsWithDefaultTableFormat(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
            ],
        ];

        $this->io->expects($this->once())
            ->method('section')
            ->with('概览')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $this->displayer->displayStatistics($data, 'unknown_format', $this->io);
    }

    public function testDisplayStatisticsWithCsvFormat(): void
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

        $this->io->expects($this->exactly(4))
            ->method('text')
            ->with(self::callback(static function (mixed $text): bool {
                if (!is_string($text)) {
                    return false;
                }
                $expectedTexts = ['指标,数值', 'overview.totalUsers,100', 'overview.activeUsers,50', 'overview.averageScore,85.50'];

                return in_array($text, $expectedTexts, true);
            }))
        ;

        $this->displayer->displayStatistics($data, 'csv', $this->io);
    }

    public function testDisplayOverviewSectionWithValidData(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'activeUsers' => 50,
                'completionRate' => 0.75,
            ],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayOverviewSection');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('section')
            ->with('概览')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                ['指标', '数值'],
                [
                    ['TotalUsers', '100.00'],
                    ['ActiveUsers', '50.00'],
                    ['CompletionRate', '0.75'],
                ]
            )
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayOverviewSectionWithMissingData(): void
    {
        $data = [];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayOverviewSection');
        $method->setAccessible(true);

        $this->io->expects($this->never())->method('section');
        $this->io->expects($this->never())->method('table');

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayOverviewSectionWithInvalidData(): void
    {
        $data = [
            'overview' => 'not_an_array',
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayOverviewSection');
        $method->setAccessible(true);

        $this->io->expects($this->never())->method('section');
        $this->io->expects($this->never())->method('table');

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testBuildOverviewRows(): void
    {
        $overview = [
            'totalUsers' => 100,
            'activeUsers' => 50,
            'completionRate' => 0.75,
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('buildOverviewRows');
        $method->setAccessible(true);

        $result = $method->invoke($this->displayer, $overview);

        $expected = [
            ['TotalUsers', '100.00'],
            ['ActiveUsers', '50.00'],
            ['CompletionRate', '0.75'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testFormatValue(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);

        // Test with numeric values
        $this->assertEquals('100.00', $method->invoke($this->displayer, 100));
        $this->assertEquals('85.50', $method->invoke($this->displayer, 85.5));
        $this->assertEquals('0.75', $method->invoke($this->displayer, 0.75));

        // Test with string values
        $this->assertEquals('test', $method->invoke($this->displayer, 'test'));
        $this->assertEquals('123', $method->invoke($this->displayer, '123'));

        // Test with other types
        $this->assertEquals('', $method->invoke($this->displayer, null));
        $this->assertEquals('', $method->invoke($this->displayer, []));
        $this->assertEquals('', $method->invoke($this->displayer, new \stdClass()));
    }

    public function testDisplayUserMetricsSection(): void
    {
        $data = [
            'userMetrics' => [
                'averageScore' => 85.5,
                'studyTime' => 120,
                'completionRate' => 0.75,
            ],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayUserMetricsSection');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('section')
            ->with('用户指标')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayUserMetricsSectionWithMissingData(): void
    {
        $data = [];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayUserMetricsSection');
        $method->setAccessible(true);

        $this->io->expects($this->never())->method('section');
        $this->io->expects($this->never())->method('table');

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayCourseMetricsSection(): void
    {
        $data = [
            'courseMetrics' => [
                'totalCourses' => 25,
                'activeCourses' => 15,
                'completionRate' => 0.80,
            ],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayCourseMetricsSection');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('section')
            ->with('课程指标')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayBehaviorAnalysisSection(): void
    {
        $data = [
            'behaviorAnalysis' => [
                'suspiciousBehaviors' => 5,
                'totalBehaviors' => 1000,
                'suspiciousRate' => 0.5,
            ],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayBehaviorAnalysisSection');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('section')
            ->with('行为分析')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testDisplayMetricsTable(): void
    {
        $metrics = [
            'averageScore' => 85.5,
            'studyTime' => 120,
            'completionRate' => 0.75,
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('displayMetricsTable');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $method->invoke($this->displayer, $metrics, $this->io);
    }

    public function testBuildMetricsRows(): void
    {
        $metrics = [
            'averageScore' => 85.5,
            'studyTime' => 120,
            'completionRate' => 0.75,
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('buildMetricsRows');
        $method->setAccessible(true);

        $result = $method->invoke($this->displayer, $metrics);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(['AverageScore', '85.50'], $result);
        $this->assertContains(['StudyTime', '120.00'], $result);
        $this->assertContains(['CompletionRate', '0.75'], $result);
    }

    public function testBuildMetricRow(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('buildMetricRow');
        $method->setAccessible(true);

        // Test with string key and numeric value
        $result = $method->invoke($this->displayer, 'testKey', 85.5);
        $this->assertEquals(['TestKey', '85.50'], $result);

        // Test with string key and string value
        $result = $method->invoke($this->displayer, 'testKey', 'testValue');
        $this->assertEquals(['TestKey', 'testValue'], $result);

        // Test with non-scalar key
        $result = $method->invoke($this->displayer, ['array'], 'value');
        $this->assertEquals(['Unknown', 'value'], $result);
    }

    public function testFormatMetricValue(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('formatMetricValue');
        $method->setAccessible(true);

        // Test with array value
        $arrayValue = ['key' => 'value'];
        $result = $method->invoke($this->displayer, $arrayValue);
        $this->assertEquals(json_encode($arrayValue, JSON_UNESCAPED_UNICODE), $result);

        // Test with numeric value
        $this->assertEquals('85.50', $method->invoke($this->displayer, 85.5));

        // Test with string value
        $this->assertEquals('test', $method->invoke($this->displayer, 'test'));

        // Test with other types
        $this->assertEquals('', $method->invoke($this->displayer, null));
        $this->assertEquals('', $method->invoke($this->displayer, new \stdClass()));
    }

    public function testOutputCsvDataWithNestedData(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
                'details' => [
                    'activeUsers' => 50,
                    'newUsers' => 10,
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('outputCsvData');
        $method->setAccessible(true);

        $this->io->expects($this->exactly(3))
            ->method('text')
            ->with(self::callback(static function (mixed $text): bool {
                if (!is_string($text)) {
                    return false;
                }
                $expectedTexts = ['overview.totalUsers,100', 'overview.details.activeUsers,50', 'overview.details.newUsers,10'];

                return in_array($text, $expectedTexts, true);
            }))
        ;

        $method->invoke($this->displayer, $data, $this->io);
    }

    public function testProcessCsvDataItem(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('processCsvDataItem');
        $method->setAccessible(true);

        // Test with array value (should recurse)
        $this->io->expects($this->atLeastOnce())
            ->method('text')
        ;

        $method->invoke($this->displayer, 'overview', ['totalUsers' => 100], $this->io, '');

        // Test with scalar value (should output line)
        $method->invoke($this->displayer, 'totalUsers', 100, $this->io, 'overview');
    }

    public function testBuildFullKey(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('buildFullKey');
        $method->setAccessible(true);

        // Test with empty prefix
        $this->assertEquals('key', $method->invoke($this->displayer, '', 'key'));

        // Test with non-empty prefix
        $this->assertEquals('overview.key', $method->invoke($this->displayer, 'overview', 'key'));
    }

    public function testOutputCsvLine(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('outputCsvLine');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('key,value')
        ;

        $method->invoke($this->displayer, 'key', 'value', $this->io);
    }

    public function testOutputCsvLineWithNonScalarValue(): void
    {
        $reflection = new \ReflectionClass($this->displayer);
        $method = $reflection->getMethod('outputCsvLine');
        $method->setAccessible(true);

        $this->io->expects($this->once())
            ->method('text')
            ->with('key,')
        ;

        $method->invoke($this->displayer, 'key', ['array'], $this->io);
    }

    public function testDisplayTableFormatWithPartialData(): void
    {
        $data = [
            'overview' => [
                'totalUsers' => 100,
            ],
            // Missing userMetrics, courseMetrics, behaviorAnalysis
        ];

        $this->io->expects($this->once())
            ->method('section')
            ->with('概览')
        ;

        $this->io->expects($this->once())
            ->method('table')
            ->with(
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg) && $arg === ['指标', '数值'];
                }),
                self::callback(static function (mixed $arg): bool {
                    return is_array($arg);
                })
            )
        ;

        $this->displayer->displayStatistics($data, 'table', $this->io);
    }

    public function testDisplayTableFormatWithEmptyData(): void
    {
        $data = [];

        $this->io->expects($this->never())->method('section');
        $this->io->expects($this->never())->method('table');

        $this->displayer->displayStatistics($data, 'table', $this->io);
    }
}
