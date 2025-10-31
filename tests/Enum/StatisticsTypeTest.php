<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

/**
 * StatisticsType 枚举测试
 *
 * @internal
 */
#[CoversClass(StatisticsType::class)]
#[RunTestsInSeparateProcesses]
final class StatisticsTypeTest extends AbstractEnumTestCase
{
    #[TestWith([StatisticsType::USER, 'user', '用户统计'])]
    #[TestWith([StatisticsType::COURSE, 'course', '课程统计'])]
    #[TestWith([StatisticsType::BEHAVIOR, 'behavior', '行为统计'])]
    #[TestWith([StatisticsType::ANOMALY, 'anomaly', '异常统计'])]
    #[TestWith([StatisticsType::DEVICE, 'device', '设备统计'])]
    #[TestWith([StatisticsType::PROGRESS, 'progress', '进度统计'])]
    #[TestWith([StatisticsType::DURATION, 'duration', '时长统计'])]
    #[TestWith([StatisticsType::EFFICIENCY, 'efficiency', '效率统计'])]
    #[TestWith([StatisticsType::COMPLETION, 'completion', '完成率统计'])]
    #[TestWith([StatisticsType::ENGAGEMENT, 'engagement', '参与度统计'])]
    #[TestWith([StatisticsType::QUALITY, 'quality', '质量统计'])]
    #[TestWith([StatisticsType::TREND, 'trend', '趋势统计'])]
    public function testValueAndLabel(StatisticsType $enum, string $expectedValue, string $expectedLabel): void
    {
        self::assertSame($expectedValue, $enum->value);
        self::assertSame($expectedLabel, $enum->getLabel());

        $array = $enum->toArray();
        self::assertSame(['value' => $expectedValue, 'label' => $expectedLabel], $array);
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = StatisticsType::USER->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('user', $result['value']);
        $this->assertEquals('用户统计', $result['label']);
    }

    /**
     * 测试获取描述
     */
    public function testGetDescription(): void
    {
        $this->assertEquals('统计用户活跃度、注册数、学习人数等用户相关指标', StatisticsType::USER->getDescription());
        $this->assertEquals('统计课程热度、完成率、评分等课程相关指标', StatisticsType::COURSE->getDescription());
        $this->assertEquals('统计学习行为模式、交互频率等行为相关指标', StatisticsType::BEHAVIOR->getDescription());
        $this->assertEquals('统计异常检测、处理效率等异常相关指标', StatisticsType::ANOMALY->getDescription());
        $this->assertEquals('统计设备类型、浏览器分布等设备相关指标', StatisticsType::DEVICE->getDescription());
        $this->assertEquals('统计学习进度、完成情况等进度相关指标', StatisticsType::PROGRESS->getDescription());
        $this->assertEquals('统计学习时长、有效时长等时长相关指标', StatisticsType::DURATION->getDescription());
        $this->assertEquals('统计学习效率、专注度等效率相关指标', StatisticsType::EFFICIENCY->getDescription());
        $this->assertEquals('统计完成率、通过率等完成相关指标', StatisticsType::COMPLETION->getDescription());
        $this->assertEquals('统计参与度、活跃度等参与相关指标', StatisticsType::ENGAGEMENT->getDescription());
        $this->assertEquals('统计学习质量、评分等质量相关指标', StatisticsType::QUALITY->getDescription());
        $this->assertEquals('统计发展趋势、变化趋势等趋势相关指标', StatisticsType::TREND->getDescription());
    }

    /**
     * 测试获取分类
     */
    public function testGetCategory(): void
    {
        // 用户相关
        $this->assertEquals('user_related', StatisticsType::USER->getCategory());
        $this->assertEquals('user_related', StatisticsType::DEVICE->getCategory());

        // 课程相关
        $this->assertEquals('course_related', StatisticsType::COURSE->getCategory());
        $this->assertEquals('course_related', StatisticsType::PROGRESS->getCategory());
        $this->assertEquals('course_related', StatisticsType::COMPLETION->getCategory());

        // 行为相关
        $this->assertEquals('behavior_related', StatisticsType::BEHAVIOR->getCategory());
        $this->assertEquals('behavior_related', StatisticsType::ENGAGEMENT->getCategory());
        $this->assertEquals('behavior_related', StatisticsType::QUALITY->getCategory());

        // 安全相关
        $this->assertEquals('security_related', StatisticsType::ANOMALY->getCategory());

        // 性能相关
        $this->assertEquals('performance_related', StatisticsType::DURATION->getCategory());
        $this->assertEquals('performance_related', StatisticsType::EFFICIENCY->getCategory());

        // 分析相关
        $this->assertEquals('analysis_related', StatisticsType::TREND->getCategory());
    }

    /**
     * 测试获取优先级
     */
    public function testGetPriority(): void
    {
        // 高优先级 (1)
        $this->assertEquals(1, StatisticsType::USER->getPriority());
        $this->assertEquals(1, StatisticsType::COURSE->getPriority());
        $this->assertEquals(1, StatisticsType::PROGRESS->getPriority());

        // 中优先级 (2)
        $this->assertEquals(2, StatisticsType::BEHAVIOR->getPriority());
        $this->assertEquals(2, StatisticsType::DURATION->getPriority());
        $this->assertEquals(2, StatisticsType::COMPLETION->getPriority());

        // 低优先级 (3)
        $this->assertEquals(3, StatisticsType::ANOMALY->getPriority());
        $this->assertEquals(3, StatisticsType::DEVICE->getPriority());
        $this->assertEquals(3, StatisticsType::EFFICIENCY->getPriority());

        // 扩展优先级 (4)
        $this->assertEquals(4, StatisticsType::ENGAGEMENT->getPriority());
        $this->assertEquals(4, StatisticsType::QUALITY->getPriority());
        $this->assertEquals(4, StatisticsType::TREND->getPriority());
    }

    /**
     * 测试是否为核心统计
     */
    public function testIsCoreStatistics(): void
    {
        // 核心统计
        $this->assertTrue(StatisticsType::USER->isCoreStatistics());
        $this->assertTrue(StatisticsType::COURSE->isCoreStatistics());
        $this->assertTrue(StatisticsType::PROGRESS->isCoreStatistics());
        $this->assertTrue(StatisticsType::DURATION->isCoreStatistics());
        $this->assertTrue(StatisticsType::COMPLETION->isCoreStatistics());

        // 非核心统计
        $this->assertFalse(StatisticsType::BEHAVIOR->isCoreStatistics());
        $this->assertFalse(StatisticsType::ANOMALY->isCoreStatistics());
        $this->assertFalse(StatisticsType::DEVICE->isCoreStatistics());
        $this->assertFalse(StatisticsType::EFFICIENCY->isCoreStatistics());
        $this->assertFalse(StatisticsType::ENGAGEMENT->isCoreStatistics());
        $this->assertFalse(StatisticsType::QUALITY->isCoreStatistics());
        $this->assertFalse(StatisticsType::TREND->isCoreStatistics());
    }

    /**
     * 测试是否需要实时更新
     */
    public function testNeedsRealTimeUpdate(): void
    {
        // 需要实时更新
        $this->assertTrue(StatisticsType::USER->needsRealTimeUpdate());
        $this->assertTrue(StatisticsType::BEHAVIOR->needsRealTimeUpdate());
        $this->assertTrue(StatisticsType::ANOMALY->needsRealTimeUpdate());
        $this->assertTrue(StatisticsType::PROGRESS->needsRealTimeUpdate());

        // 不需要实时更新
        $this->assertFalse(StatisticsType::COURSE->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::DEVICE->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::DURATION->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::EFFICIENCY->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::COMPLETION->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::ENGAGEMENT->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::QUALITY->needsRealTimeUpdate());
        $this->assertFalse(StatisticsType::TREND->needsRealTimeUpdate());
    }

    /**
     * 测试获取所有统计类型
     */
    public function testGetAllTypes(): void
    {
        $types = StatisticsType::getAllTypes();

        $this->assertCount(13, $types);
        $this->assertContains(StatisticsType::USER, $types);
        $this->assertContains(StatisticsType::COURSE, $types);
        $this->assertContains(StatisticsType::BEHAVIOR, $types);
        $this->assertContains(StatisticsType::ANOMALY, $types);
        $this->assertContains(StatisticsType::DEVICE, $types);
        $this->assertContains(StatisticsType::PROGRESS, $types);
        $this->assertContains(StatisticsType::DURATION, $types);
        $this->assertContains(StatisticsType::EFFICIENCY, $types);
        $this->assertContains(StatisticsType::COMPLETION, $types);
        $this->assertContains(StatisticsType::ENGAGEMENT, $types);
        $this->assertContains(StatisticsType::QUALITY, $types);
        $this->assertContains(StatisticsType::TREND, $types);
        $this->assertContains(StatisticsType::SYSTEM, $types);
    }

    /**
     * 测试按分类获取统计类型
     */
    public function testGetByCategory(): void
    {
        // 用户相关
        $userRelated = StatisticsType::getByCategory('user_related');
        $this->assertCount(2, $userRelated);
        $this->assertContains(StatisticsType::USER, $userRelated);
        $this->assertContains(StatisticsType::DEVICE, $userRelated);

        // 课程相关
        $courseRelated = StatisticsType::getByCategory('course_related');
        $this->assertCount(3, $courseRelated);
        $this->assertContains(StatisticsType::COURSE, $courseRelated);
        $this->assertContains(StatisticsType::PROGRESS, $courseRelated);
        $this->assertContains(StatisticsType::COMPLETION, $courseRelated);

        // 行为相关
        $behaviorRelated = StatisticsType::getByCategory('behavior_related');
        $this->assertCount(3, $behaviorRelated);
        $this->assertContains(StatisticsType::BEHAVIOR, $behaviorRelated);
        $this->assertContains(StatisticsType::ENGAGEMENT, $behaviorRelated);
        $this->assertContains(StatisticsType::QUALITY, $behaviorRelated);

        // 安全相关
        $securityRelated = StatisticsType::getByCategory('security_related');
        $this->assertCount(1, $securityRelated);
        $this->assertContains(StatisticsType::ANOMALY, $securityRelated);

        // 性能相关
        $performanceRelated = StatisticsType::getByCategory('performance_related');
        $this->assertCount(2, $performanceRelated);
        $this->assertContains(StatisticsType::DURATION, $performanceRelated);
        $this->assertContains(StatisticsType::EFFICIENCY, $performanceRelated);

        // 分析相关
        $analysisRelated = StatisticsType::getByCategory('analysis_related');
        $this->assertCount(1, $analysisRelated);
        $this->assertContains(StatisticsType::TREND, $analysisRelated);
    }

    /**
     * 测试获取核心统计类型
     */
    public function testGetCoreTypes(): void
    {
        $coreTypes = StatisticsType::getCoreTypes();

        $this->assertCount(5, $coreTypes);
        $this->assertContains(StatisticsType::USER, $coreTypes);
        $this->assertContains(StatisticsType::COURSE, $coreTypes);
        $this->assertContains(StatisticsType::PROGRESS, $coreTypes);
        $this->assertContains(StatisticsType::DURATION, $coreTypes);
        $this->assertContains(StatisticsType::COMPLETION, $coreTypes);
    }

    /**
     * 测试按优先级排序
     */
    public function testGetSortedByPriority(): void
    {
        $sorted = StatisticsType::getSortedByPriority();

        $this->assertCount(13, $sorted);

        // 验证排序顺序
        $previousPriority = 0;
        foreach ($sorted as $type) {
            $currentPriority = $type->getPriority();
            $this->assertGreaterThanOrEqual($previousPriority, $currentPriority);
            $previousPriority = $currentPriority;
        }

        // 验证高优先级排在前面
        $firstThree = array_slice($sorted, 0, 3);
        foreach ($firstThree as $type) {
            $this->assertEquals(1, $type->getPriority());
        }
    }

    /**
     * 测试枚举 cases
     */
    public function testCases(): void
    {
        $cases = StatisticsType::cases();

        $this->assertCount(13, $cases);
        foreach ($cases as $case) {
            $this->assertNotEmpty($case->getLabel());
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function testFrom(): void
    {
        self::assertSame(StatisticsType::USER, StatisticsType::from('user'));
        self::assertSame(StatisticsType::COURSE, StatisticsType::from('course'));
        self::assertSame(StatisticsType::ANOMALY, StatisticsType::from('anomaly'));
    }

    /**
     * 测试 tryFrom
     */
    public function testTryFrom(): void
    {
        self::assertSame(StatisticsType::USER, StatisticsType::tryFrom('user'));
        self::assertSame(StatisticsType::TREND, StatisticsType::tryFrom('trend'));
        self::assertNull(StatisticsType::tryFrom('invalid_type'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (StatisticsType $case) => $case->value, StatisticsType::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'Enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (StatisticsType $case) => $case->getLabel(), StatisticsType::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'Enum labels must be unique');
    }
}
