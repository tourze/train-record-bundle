<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\BehaviorDataProcessor;

/**
 * @internal
 */
#[CoversClass(BehaviorDataProcessor::class)]
#[RunTestsInSeparateProcesses]
final class BehaviorDataProcessorTest extends AbstractIntegrationTestCase
{
    private BehaviorDataProcessor $behaviorProcessor;

    protected function onSetUp(): void
    {
        $this->behaviorProcessor = self::getService(BehaviorDataProcessor::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BehaviorDataProcessor::class, $this->behaviorProcessor);
    }

    public function testConvertToStatsWithEmptyData(): void
    {
        $result = $this->behaviorProcessor->convertToStats([]);
        $this->assertNull($result);
    }

    public function testConvertToStatsWithValidData(): void
    {
        $behaviorData = [
            [
                'action' => 'click',
                'timestamp' => 1640995200,
                'duration' => 10.5,
                'target' => 'button',
            ],
            [
                'action' => 'scroll',
                'timestamp' => 1640995210,
                'duration' => 5.0,
                'position' => 100,
            ],
        ];

        $result = $this->behaviorProcessor->convertToStats($behaviorData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertEquals('scroll', $result['action']);
        $this->assertEquals(1640995210, $result['timestamp']);
        $this->assertEquals(5.0, $result['duration']);
        $this->assertEquals(100, $result['position']);
    }

    public function testConvertStatsToDataFormatWithEmptyData(): void
    {
        $result = $this->behaviorProcessor->convertStatsToDataFormat([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConvertStatsToDataFormatWithValidIndexedFormat(): void
    {
        // 使用string key的关联数组来测试isAlreadyValidFormat的逻辑
        // 但实际上这个方法会转换成索引数组格式
        $behaviorStats = [
            'action_click' => 'click',
            'action_scroll' => 'scroll',
            'duration_sum' => 15.5,
        ];

        $result = $this->behaviorProcessor->convertStatsToDataFormat($behaviorStats);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        // 验证转换后每个元素都是stat格式
        foreach ($result as $item) {
            $this->assertIsArray($item);
            $this->assertEquals('stat', $item['type']);
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('value', $item);
        }
    }

    public function testConvertStatsToDataFormatWithAssociativeData(): void
    {
        $behaviorStats = [
            'total_clicks' => 5,
            'total_scrolls' => 3,
            'average_duration' => 7.5,
        ];

        $result = $this->behaviorProcessor->convertStatsToDataFormat($behaviorStats);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // 验证转换后的格式
        foreach ($result as $item) {
            $this->assertIsArray($item);
            $this->assertEquals('stat', $item['type']);
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('timestamp', $item);
            $this->assertIsInt($item['timestamp']);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    #[DataProvider('focusRatioDataProvider')]
    public function testCalculateFocusRatio(array $behaviorData, float $expectedRatio): void
    {
        $result = $this->behaviorProcessor->calculateFocusRatio($behaviorData);
        $this->assertEqualsWithDelta($expectedRatio, $result, 0.01);
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, float}>
     */
    public static function focusRatioDataProvider(): array
    {
        return [
            'empty data' => [[], 0.0],
            'all focused behavior' => [
                [
                    ['action' => 'click', 'duration' => 10.0],
                    ['action' => 'scroll', 'duration' => 20.0],
                ],
                1.0,
            ],
            'mixed behavior' => [
                [
                    ['action' => 'click', 'duration' => 10.0],
                    ['action' => 'window_blur', 'duration' => 10.0],
                ],
                0.5,
            ],
            'all unfocused behavior' => [
                [
                    ['action' => 'window_blur', 'duration' => 10.0],
                    ['action' => 'mouse_leave', 'duration' => 15.0],
                    ['action' => 'tab_switch', 'duration' => 5.0],
                ],
                0.0,
            ],
            'mostly focused with some unfocused' => [
                [
                    ['action' => 'click', 'duration' => 150.0],
                    ['action' => 'window_blur', 'duration' => 10.0],
                ],
                0.9375, // 150 / (150 + 10) = 0.9375
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    #[DataProvider('interactionRatioDataProvider')]
    public function testCalculateInteractionRatio(array $behaviorData, float $expectedRatio): void
    {
        $result = $this->behaviorProcessor->calculateInteractionRatio($behaviorData);
        $this->assertEqualsWithDelta($expectedRatio, $result, 0.01);
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, float}>
     */
    public static function interactionRatioDataProvider(): array
    {
        return [
            'empty data' => [[], 0.0],
            'all interactive behavior' => [
                [
                    ['action' => 'click'],
                    ['action' => 'scroll'],
                    ['action' => 'key_press'],
                    ['action' => 'video_control'],
                ],
                1.0,
            ],
            'mixed behavior' => [
                [
                    ['action' => 'click'],
                    ['action' => 'window_blur'],
                    ['action' => 'scroll'],
                    ['action' => 'idle'],
                ],
                0.5,
            ],
            'no interactive behavior' => [
                [
                    ['action' => 'window_blur'],
                    ['action' => 'idle'],
                    ['action' => 'pause'],
                ],
                0.0,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    #[DataProvider('continuityRatioDataProvider')]
    public function testCalculateContinuityRatio(array $behaviorData, float $expectedRatio): void
    {
        $result = $this->behaviorProcessor->calculateContinuityRatio($behaviorData);
        $this->assertEqualsWithDelta($expectedRatio, $result, 0.01);
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, float}>
     */
    public static function continuityRatioDataProvider(): array
    {
        return [
            'empty data' => [[], 0.0],
            'continuous behavior' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995260], // 60 seconds later
                    ['timestamp' => 1640995320], // 120 seconds from start
                ],
                1.0,
            ],
            'one gap over 120 seconds' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995260],
                    ['timestamp' => 1640995381], // 121 seconds from second timestamp (gap)
                ],
                0.67, // 1 - (1/3)
            ],
            'multiple gaps' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995321], // Gap > 120
                    ['timestamp' => 1640995442], // Gap > 120
                    ['timestamp' => 1640995563], // Gap > 120
                ],
                0.25, // 1 - (3/4)
            ],
            'with null timestamps' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => null],
                    ['timestamp' => 1640995260],
                    ['timestamp' => ''],
                    ['timestamp' => 1640995320],
                ],
                1.0, // Null timestamps are ignored
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    #[DataProvider('browsingTestingDataProvider')]
    public function testIsBrowsingOrTesting(array $behaviorData, bool $expected): void
    {
        $result = $this->behaviorProcessor->isBrowsingOrTesting($behaviorData);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, bool}>
     */
    public static function browsingTestingDataProvider(): array
    {
        return [
            'empty data' => [[], false],
            'browsing info' => [
                [['action' => 'browse_info']],
                true,
            ],
            'view materials' => [
                [['action' => 'view_materials']],
                true,
            ],
            'take test' => [
                [['action' => 'take_test']],
                true,
            ],
            'quiz attempt' => [
                [['action' => 'quiz_attempt']],
                true,
            ],
            'normal behavior' => [
                [
                    ['action' => 'click'],
                    ['action' => 'scroll'],
                ],
                false,
            ],
            'mixed with browsing' => [
                [
                    ['action' => 'click'],
                    ['action' => 'browse_info'],
                    ['action' => 'scroll'],
                ],
                true,
            ],
        ];
    }

    public function testHasAuthenticationFailure(): void
    {
        $behaviorDataWithAuthFailure = [
            ['action' => 'click'],
            ['action' => 'auth_failed'],
            ['action' => 'scroll'],
        ];

        $behaviorDataWithoutAuthFailure = [
            ['action' => 'click'],
            ['action' => 'scroll'],
        ];

        $emptyBehaviorData = [];

        $this->assertTrue($this->behaviorProcessor->hasAuthenticationFailure($behaviorDataWithAuthFailure));
        $this->assertFalse($this->behaviorProcessor->hasAuthenticationFailure($behaviorDataWithoutAuthFailure));
        $this->assertFalse($this->behaviorProcessor->hasAuthenticationFailure($emptyBehaviorData));
    }

    public function testHasCompletedTest(): void
    {
        $behaviorDataWithTestCompleted = [
            ['action' => 'click'],
            ['action' => 'test_completed'],
            ['action' => 'scroll'],
        ];

        $behaviorDataWithoutTestCompleted = [
            ['action' => 'click'],
            ['action' => 'scroll'],
        ];

        $emptyBehaviorData = [];

        $this->assertTrue($this->behaviorProcessor->hasCompletedTest($behaviorDataWithTestCompleted));
        $this->assertFalse($this->behaviorProcessor->hasCompletedTest($behaviorDataWithoutTestCompleted));
        $this->assertFalse($this->behaviorProcessor->hasCompletedTest($emptyBehaviorData));
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     * @param array<string, mixed> $expectedResult
     */
    #[DataProvider('interactionTimeoutDataProvider')]
    public function testCheckInteractionTimeout(array $behaviorData, int $maxInterval, array $expectedResult): void
    {
        $result = $this->behaviorProcessor->checkInteractionTimeout($behaviorData, $maxInterval);

        $this->assertEquals($expectedResult['valid'], $result['valid']);
        if (array_key_exists('description', $expectedResult)) {
            $this->assertArrayHasKey('description', $result);
            $this->assertEquals($expectedResult['description'], $result['description']);
        } else {
            $this->assertArrayNotHasKey('description', $result);
        }
    }

    /**
     * @return array<string, array{array<int, array<string, mixed>>, int, array<string, mixed>}>
     */
    public static function interactionTimeoutDataProvider(): array
    {
        return [
            'empty data' => [[], 300, ['valid' => true]],
            'valid intervals' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995260], // 60 seconds later
                    ['timestamp' => 1640995320], // 120 seconds later
                ],
                300,
                ['valid' => true],
            ],
            'timeout exceeded' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995501], // 301 seconds later (exceeds 300)
                ],
                300,
                [
                    'valid' => false,
                    'description' => '交互间隔超过300秒',
                ],
            ],
            'with null timestamps' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => null],
                    ['timestamp' => 1640995260],
                ],
                300,
                ['valid' => true],
            ],
            'custom timeout' => [
                [
                    ['timestamp' => 1640995200],
                    ['timestamp' => 1640995701], // 501 seconds later
                ],
                500,
                [
                    'valid' => false,
                    'description' => '交互间隔超过500秒',
                ],
            ],
        ];
    }

    public function testBuildEvidenceData(): void
    {
        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200, 'duration' => 10.0],
            ['action' => 'scroll', 'timestamp' => 1640995260, 'duration' => 5.0],
            ['action' => 'click', 'timestamp' => 1640995320, 'duration' => 8.0],
        ];
        $totalDuration = 3600.0; // 1 hour

        $result = $this->behaviorProcessor->buildEvidenceData($behaviorData, $totalDuration);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $evidence = $result[0];
        $this->assertEquals('behavior_summary', $evidence['type']);
        $this->assertEquals(3, $evidence['total_behaviors']);
        $this->assertIsArray($evidence['unique_actions']);
        $this->assertContains('click', $evidence['unique_actions']);
        $this->assertContains('scroll', $evidence['unique_actions']);

        $this->assertIsArray($evidence['timestamp_range']);
        $this->assertEquals(1640995200, $evidence['timestamp_range']['start']);
        $this->assertEquals(1640995320, $evidence['timestamp_range']['end']);

        $this->assertEqualsWithDelta(0.05, $evidence['interaction_frequency'], 0.01); // 0.05 behaviors per minute = 3 behaviors per hour
        $this->assertIsInt($evidence['timestamp']);
    }

    public function testBuildEvidenceDataWithEmptyTimestamps(): void
    {
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'scroll', 'duration' => 5.0],
        ];
        $totalDuration = 1800.0; // 30 minutes

        $result = $this->behaviorProcessor->buildEvidenceData($behaviorData, $totalDuration);

        $this->assertIsArray($result);
        $evidence = $result[0];
        $this->assertNull($evidence['timestamp_range']);
        $this->assertEqualsWithDelta(0.0667, $evidence['interaction_frequency'], 0.001); // 2 behaviors per 30 minutes = ~0.0667 per minute
    }

    public function testBuildEvidenceDataWithZeroDuration(): void
    {
        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200],
        ];
        $totalDuration = 0.0;

        $result = $this->behaviorProcessor->buildEvidenceData($behaviorData, $totalDuration);

        $this->assertIsArray($result);
        $evidence = $result[0];
        $this->assertEquals(0.0, $evidence['interaction_frequency']);
    }
}
