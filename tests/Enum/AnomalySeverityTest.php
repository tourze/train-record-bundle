<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;

/**
 * AnomalySeverity 枚举测试
 *
 * @internal
 */
#[CoversClass(AnomalySeverity::class)]
#[RunTestsInSeparateProcesses]
final class AnomalySeverityTest extends AbstractEnumTestCase
{
    public function testEnumCasesExist(): void
    {
        $cases = AnomalySeverity::cases();

        self::assertCount(4, $cases);
        self::assertContainsOnlyInstancesOf(AnomalySeverity::class, $cases);
    }

    #[TestWith([AnomalySeverity::LOW, 'low', '低'])]
    #[TestWith([AnomalySeverity::MEDIUM, 'medium', '中'])]
    #[TestWith([AnomalySeverity::HIGH, 'high', '高'])]
    #[TestWith([AnomalySeverity::CRITICAL, 'critical', '严重'])]
    public function testValueAndLabel(AnomalySeverity $enum, string $expectedValue, string $expectedLabel): void
    {
        self::assertSame($expectedValue, $enum->value);
        self::assertSame($expectedLabel, $enum->getLabel());

        // Test toArray format
        $array = $enum->toArray();
        self::assertIsArray($array);
        self::assertCount(2, $array);
        self::assertArrayHasKey('value', $array);
        self::assertArrayHasKey('label', $array);
        self::assertSame($expectedValue, $array['value']);
        self::assertSame($expectedLabel, $array['label']);
    }

    /**
     * 测试获取描述
     */
    public function testGetDescription(): void
    {
        $this->assertEquals('轻微异常，可延后处理', AnomalySeverity::LOW->getDescription());
        $this->assertEquals('一般异常，需要关注', AnomalySeverity::MEDIUM->getDescription());
        $this->assertEquals('重要异常，需要及时处理', AnomalySeverity::HIGH->getDescription());
        $this->assertEquals('严重异常，需要立即处理', AnomalySeverity::CRITICAL->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function testGetColor(): void
    {
        $this->assertEquals('green', AnomalySeverity::LOW->getColor());
        $this->assertEquals('yellow', AnomalySeverity::MEDIUM->getColor());
        $this->assertEquals('orange', AnomalySeverity::HIGH->getColor());
        $this->assertEquals('red', AnomalySeverity::CRITICAL->getColor());
    }

    /**
     * 测试获取权重
     */
    public function testGetWeight(): void
    {
        $this->assertEquals(1, AnomalySeverity::LOW->getWeight());
        $this->assertEquals(2, AnomalySeverity::MEDIUM->getWeight());
        $this->assertEquals(3, AnomalySeverity::HIGH->getWeight());
        $this->assertEquals(4, AnomalySeverity::CRITICAL->getWeight());
    }

    /**
     * 测试是否需要立即处理
     */
    public function testRequiresImmediateAction(): void
    {
        $this->assertFalse(AnomalySeverity::LOW->requiresImmediateAction());
        $this->assertFalse(AnomalySeverity::MEDIUM->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::HIGH->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::CRITICAL->requiresImmediateAction());
    }

    /**
     * 测试是否为高优先级
     */
    public function testIsHighPriority(): void
    {
        $this->assertFalse(AnomalySeverity::LOW->isHighPriority());
        $this->assertFalse(AnomalySeverity::MEDIUM->isHighPriority());
        $this->assertTrue(AnomalySeverity::HIGH->isHighPriority());
        $this->assertTrue(AnomalySeverity::CRITICAL->isHighPriority());
    }

    /**
     * 测试处理时限
     */
    public function testGetProcessingTimeLimit(): void
    {
        $this->assertEquals(72, AnomalySeverity::LOW->getProcessingTimeLimit());
        $this->assertEquals(24, AnomalySeverity::MEDIUM->getProcessingTimeLimit());
        $this->assertEquals(4, AnomalySeverity::HIGH->getProcessingTimeLimit());
        $this->assertEquals(1, AnomalySeverity::CRITICAL->getProcessingTimeLimit());
    }

    /**
     * 测试获取所有严重程度
     */
    public function testGetAllSeverities(): void
    {
        $severities = AnomalySeverity::getAllSeverities();

        $this->assertCount(4, $severities);
        $this->assertContains(AnomalySeverity::LOW, $severities);
        $this->assertContains(AnomalySeverity::MEDIUM, $severities);
        $this->assertContains(AnomalySeverity::HIGH, $severities);
        $this->assertContains(AnomalySeverity::CRITICAL, $severities);
    }

    /**
     * 测试按权重排序
     */
    public function testGetSortedByWeight(): void
    {
        $sorted = AnomalySeverity::getSortedByWeight();

        $this->assertCount(4, $sorted);
        $this->assertEquals(AnomalySeverity::CRITICAL, $sorted[0]);
        $this->assertEquals(AnomalySeverity::HIGH, $sorted[1]);
        $this->assertEquals(AnomalySeverity::MEDIUM, $sorted[2]);
        $this->assertEquals(AnomalySeverity::LOW, $sorted[3]);
    }

    /**
     * 测试从字符串创建
     */
    public function testFromString(): void
    {
        // 英文
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('low'));
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalySeverity::fromString('medium'));
        $this->assertEquals(AnomalySeverity::HIGH, AnomalySeverity::fromString('high'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('critical'));

        // 中文
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('低'));
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalySeverity::fromString('中'));
        $this->assertEquals(AnomalySeverity::HIGH, AnomalySeverity::fromString('高'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('严重'));

        // 大写
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('LOW'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('CRITICAL'));

        // 无效值
        $this->assertNull(AnomalySeverity::fromString('invalid'));
        $this->assertNull(AnomalySeverity::fromString(''));
    }

    public function testFromWithValidValue(): void
    {
        self::assertSame(AnomalySeverity::LOW, AnomalySeverity::from('low'));
        self::assertSame(AnomalySeverity::MEDIUM, AnomalySeverity::from('medium'));
        self::assertSame(AnomalySeverity::HIGH, AnomalySeverity::from('high'));
        self::assertSame(AnomalySeverity::CRITICAL, AnomalySeverity::from('critical'));
    }

    public function testTryFromWithValidValue(): void
    {
        self::assertSame(AnomalySeverity::LOW, AnomalySeverity::tryFrom('low'));
        self::assertSame(AnomalySeverity::MEDIUM, AnomalySeverity::tryFrom('medium'));
        self::assertSame(AnomalySeverity::HIGH, AnomalySeverity::tryFrom('high'));
        self::assertSame(AnomalySeverity::CRITICAL, AnomalySeverity::tryFrom('critical'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (AnomalySeverity $case) => $case->value, AnomalySeverity::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (AnomalySeverity $case) => $case->getLabel(), AnomalySeverity::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels must be unique');
    }

    public function testToSelectItemReturnsCorrectFormat(): void
    {
        $selectItem = AnomalySeverity::LOW->toSelectItem();

        self::assertIsArray($selectItem);
        self::assertCount(4, $selectItem);
        self::assertArrayHasKey('value', $selectItem);
        self::assertArrayHasKey('label', $selectItem);
        self::assertArrayHasKey('text', $selectItem);
        self::assertArrayHasKey('name', $selectItem);

        self::assertSame('low', $selectItem['value']);
        self::assertSame('低', $selectItem['label']);
        self::assertSame('低', $selectItem['text']);
        self::assertSame('低', $selectItem['name']);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = AnomalySeverity::LOW->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('low', $result['value']);
        $this->assertEquals('低', $result['label']);
    }

    /**
     * 测试徽章样式类
     */
    public function testGetBadgeClass(): void
    {
        $this->assertEquals('bg-success', AnomalySeverity::LOW->getBadgeClass());
        $this->assertEquals('bg-warning', AnomalySeverity::MEDIUM->getBadgeClass());
        $this->assertEquals('bg-danger', AnomalySeverity::HIGH->getBadgeClass());
        $this->assertEquals('bg-dark', AnomalySeverity::CRITICAL->getBadgeClass());
    }

    /**
     * 测试徽章标识
     */
    public function testGetBadge(): void
    {
        // getBadge() 应该返回与 getBadgeClass() 相同的值
        $this->assertEquals(AnomalySeverity::LOW->getBadgeClass(), AnomalySeverity::LOW->getBadge());
        $this->assertEquals(AnomalySeverity::MEDIUM->getBadgeClass(), AnomalySeverity::MEDIUM->getBadge());
        $this->assertEquals(AnomalySeverity::HIGH->getBadgeClass(), AnomalySeverity::HIGH->getBadge());
        $this->assertEquals(AnomalySeverity::CRITICAL->getBadgeClass(), AnomalySeverity::CRITICAL->getBadge());
    }
}
