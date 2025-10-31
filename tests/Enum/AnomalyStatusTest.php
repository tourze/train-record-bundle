<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;

/**
 * AnomalyStatus 枚举测试
 *
 * @internal
 */
#[CoversClass(AnomalyStatus::class)]
#[RunTestsInSeparateProcesses]
final class AnomalyStatusTest extends AbstractEnumTestCase
{
    public function testEnumCasesExist(): void
    {
        $cases = AnomalyStatus::cases();

        self::assertCount(5, $cases);
        self::assertContainsOnlyInstancesOf(AnomalyStatus::class, $cases);
    }

    #[TestWith([AnomalyStatus::DETECTED, 'detected', '已检测'])]
    #[TestWith([AnomalyStatus::ACTIVE, 'active', '活跃状态'])]
    #[TestWith([AnomalyStatus::INVESTIGATING, 'investigating', '调查中'])]
    #[TestWith([AnomalyStatus::RESOLVED, 'resolved', '已解决'])]
    #[TestWith([AnomalyStatus::IGNORED, 'ignored', '已忽略'])]
    public function testValueAndLabel(AnomalyStatus $enum, string $expectedValue, string $expectedLabel): void
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
        $this->assertEquals('异常已被检测到，等待处理', AnomalyStatus::DETECTED->getDescription());
        $this->assertEquals('异常正在调查处理中', AnomalyStatus::INVESTIGATING->getDescription());
        $this->assertEquals('异常已被成功解决', AnomalyStatus::RESOLVED->getDescription());
        $this->assertEquals('异常被标记为忽略', AnomalyStatus::IGNORED->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function testGetColor(): void
    {
        $this->assertEquals('red', AnomalyStatus::DETECTED->getColor());
        $this->assertEquals('orange', AnomalyStatus::INVESTIGATING->getColor());
        $this->assertEquals('green', AnomalyStatus::RESOLVED->getColor());
        $this->assertEquals('gray', AnomalyStatus::IGNORED->getColor());
    }

    /**
     * 测试获取图标
     */
    public function testGetIcon(): void
    {
        $this->assertEquals('warning', AnomalyStatus::DETECTED->getIcon());
        $this->assertEquals('search', AnomalyStatus::INVESTIGATING->getIcon());
        $this->assertEquals('check', AnomalyStatus::RESOLVED->getIcon());
        $this->assertEquals('minus', AnomalyStatus::IGNORED->getIcon());
    }

    /**
     * 测试是否为活跃状态
     */
    public function testIsActive(): void
    {
        $this->assertTrue(AnomalyStatus::DETECTED->isActive());
        $this->assertTrue(AnomalyStatus::INVESTIGATING->isActive());
        $this->assertFalse(AnomalyStatus::RESOLVED->isActive());
        $this->assertFalse(AnomalyStatus::IGNORED->isActive());
    }

    /**
     * 测试是否已完成处理
     */
    public function testIsCompleted(): void
    {
        $this->assertFalse(AnomalyStatus::DETECTED->isCompleted());
        $this->assertFalse(AnomalyStatus::INVESTIGATING->isCompleted());
        $this->assertTrue(AnomalyStatus::RESOLVED->isCompleted());
        $this->assertTrue(AnomalyStatus::IGNORED->isCompleted());
    }

    /**
     * 测试是否需要处理
     */
    public function testNeedsProcessing(): void
    {
        $this->assertTrue(AnomalyStatus::DETECTED->needsProcessing());
        $this->assertFalse(AnomalyStatus::INVESTIGATING->needsProcessing());
        $this->assertFalse(AnomalyStatus::RESOLVED->needsProcessing());
        $this->assertFalse(AnomalyStatus::IGNORED->needsProcessing());
    }

    /**
     * 测试是否正在处理
     */
    public function testIsProcessing(): void
    {
        $this->assertFalse(AnomalyStatus::DETECTED->isProcessing());
        $this->assertTrue(AnomalyStatus::INVESTIGATING->isProcessing());
        $this->assertFalse(AnomalyStatus::RESOLVED->isProcessing());
        $this->assertFalse(AnomalyStatus::IGNORED->isProcessing());
    }

    /**
     * 测试获取下一个可能的状态
     */
    public function testGetNextPossibleStatuses(): void
    {
        $detected = AnomalyStatus::DETECTED->getNextPossibleStatuses();
        $this->assertCount(4, $detected);
        $this->assertContains(AnomalyStatus::ACTIVE, $detected);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $detected);
        $this->assertContains(AnomalyStatus::RESOLVED, $detected);
        $this->assertContains(AnomalyStatus::IGNORED, $detected);

        $investigating = AnomalyStatus::INVESTIGATING->getNextPossibleStatuses();
        $this->assertCount(2, $investigating);
        $this->assertContains(AnomalyStatus::RESOLVED, $investigating);
        $this->assertContains(AnomalyStatus::IGNORED, $investigating);

        $resolved = AnomalyStatus::RESOLVED->getNextPossibleStatuses();
        $this->assertCount(0, $resolved);

        $ignored = AnomalyStatus::IGNORED->getNextPossibleStatuses();
        $this->assertCount(1, $ignored);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $ignored);
    }

    /**
     * 测试状态转换检查
     */
    public function testCanTransitionTo(): void
    {
        // DETECTED 可以转换到的状态
        $this->assertTrue(AnomalyStatus::DETECTED->canTransitionTo(AnomalyStatus::INVESTIGATING));
        $this->assertTrue(AnomalyStatus::DETECTED->canTransitionTo(AnomalyStatus::RESOLVED));
        $this->assertTrue(AnomalyStatus::DETECTED->canTransitionTo(AnomalyStatus::IGNORED));
        $this->assertFalse(AnomalyStatus::DETECTED->canTransitionTo(AnomalyStatus::DETECTED));

        // INVESTIGATING 可以转换到的状态
        $this->assertTrue(AnomalyStatus::INVESTIGATING->canTransitionTo(AnomalyStatus::RESOLVED));
        $this->assertTrue(AnomalyStatus::INVESTIGATING->canTransitionTo(AnomalyStatus::IGNORED));
        $this->assertFalse(AnomalyStatus::INVESTIGATING->canTransitionTo(AnomalyStatus::DETECTED));
        $this->assertFalse(AnomalyStatus::INVESTIGATING->canTransitionTo(AnomalyStatus::INVESTIGATING));

        // RESOLVED 不能转换到任何状态
        $this->assertFalse(AnomalyStatus::RESOLVED->canTransitionTo(AnomalyStatus::DETECTED));
        $this->assertFalse(AnomalyStatus::RESOLVED->canTransitionTo(AnomalyStatus::INVESTIGATING));
        $this->assertFalse(AnomalyStatus::RESOLVED->canTransitionTo(AnomalyStatus::RESOLVED));
        $this->assertFalse(AnomalyStatus::RESOLVED->canTransitionTo(AnomalyStatus::IGNORED));

        // IGNORED 只能重新调查
        $this->assertTrue(AnomalyStatus::IGNORED->canTransitionTo(AnomalyStatus::INVESTIGATING));
        $this->assertFalse(AnomalyStatus::IGNORED->canTransitionTo(AnomalyStatus::DETECTED));
        $this->assertFalse(AnomalyStatus::IGNORED->canTransitionTo(AnomalyStatus::RESOLVED));
        $this->assertFalse(AnomalyStatus::IGNORED->canTransitionTo(AnomalyStatus::IGNORED));
    }

    /**
     * 测试获取所有状态
     */
    public function testGetAllStatuses(): void
    {
        $statuses = AnomalyStatus::getAllStatuses();

        $this->assertCount(4, $statuses);
        $this->assertContains(AnomalyStatus::DETECTED, $statuses);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $statuses);
        $this->assertContains(AnomalyStatus::RESOLVED, $statuses);
        $this->assertContains(AnomalyStatus::IGNORED, $statuses);
    }

    /**
     * 测试获取活跃状态
     */
    public function testGetActiveStatuses(): void
    {
        $statuses = AnomalyStatus::getActiveStatuses();

        $this->assertCount(2, $statuses);
        $this->assertContains(AnomalyStatus::DETECTED, $statuses);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $statuses);
    }

    /**
     * 测试获取已完成状态
     */
    public function testGetCompletedStatuses(): void
    {
        $statuses = AnomalyStatus::getCompletedStatuses();

        $this->assertCount(2, $statuses);
        $this->assertContains(AnomalyStatus::RESOLVED, $statuses);
        $this->assertContains(AnomalyStatus::IGNORED, $statuses);
    }

    /**
     * 测试从字符串创建
     */
    public function testFromString(): void
    {
        // 英文
        $this->assertEquals(AnomalyStatus::DETECTED, AnomalyStatus::fromString('detected'));
        $this->assertEquals(AnomalyStatus::INVESTIGATING, AnomalyStatus::fromString('investigating'));
        $this->assertEquals(AnomalyStatus::RESOLVED, AnomalyStatus::fromString('resolved'));
        $this->assertEquals(AnomalyStatus::IGNORED, AnomalyStatus::fromString('ignored'));

        // 中文
        $this->assertEquals(AnomalyStatus::DETECTED, AnomalyStatus::fromString('已检测'));
        $this->assertEquals(AnomalyStatus::INVESTIGATING, AnomalyStatus::fromString('调查中'));
        $this->assertEquals(AnomalyStatus::RESOLVED, AnomalyStatus::fromString('已解决'));
        $this->assertEquals(AnomalyStatus::IGNORED, AnomalyStatus::fromString('已忽略'));

        // 大写
        $this->assertEquals(AnomalyStatus::DETECTED, AnomalyStatus::fromString('DETECTED'));
        $this->assertEquals(AnomalyStatus::RESOLVED, AnomalyStatus::fromString('RESOLVED'));

        // 无效值
        $this->assertNull(AnomalyStatus::fromString('invalid'));
        $this->assertNull(AnomalyStatus::fromString(''));
    }

    public function testFromWithValidValue(): void
    {
        self::assertSame(AnomalyStatus::DETECTED, AnomalyStatus::from('detected'));
        self::assertSame(AnomalyStatus::INVESTIGATING, AnomalyStatus::from('investigating'));
        self::assertSame(AnomalyStatus::RESOLVED, AnomalyStatus::from('resolved'));
        self::assertSame(AnomalyStatus::IGNORED, AnomalyStatus::from('ignored'));
    }

    public function testTryFromWithValidValue(): void
    {
        self::assertSame(AnomalyStatus::DETECTED, AnomalyStatus::tryFrom('detected'));
        self::assertSame(AnomalyStatus::INVESTIGATING, AnomalyStatus::tryFrom('investigating'));
        self::assertSame(AnomalyStatus::RESOLVED, AnomalyStatus::tryFrom('resolved'));
        self::assertSame(AnomalyStatus::IGNORED, AnomalyStatus::tryFrom('ignored'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (AnomalyStatus $case) => $case->value, AnomalyStatus::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (AnomalyStatus $case) => $case->getLabel(), AnomalyStatus::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels must be unique');
    }

    public function testToSelectItemReturnsCorrectFormat(): void
    {
        $selectItem = AnomalyStatus::DETECTED->toSelectItem();

        self::assertIsArray($selectItem);
        self::assertCount(4, $selectItem);
        self::assertArrayHasKey('value', $selectItem);
        self::assertArrayHasKey('label', $selectItem);
        self::assertArrayHasKey('text', $selectItem);
        self::assertArrayHasKey('name', $selectItem);

        self::assertSame('detected', $selectItem['value']);
        self::assertSame('已检测', $selectItem['label']);
        self::assertSame('已检测', $selectItem['text']);
        self::assertSame('已检测', $selectItem['name']);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = AnomalyStatus::DETECTED->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('detected', $result['value']);
        $this->assertEquals('已检测', $result['label']);
    }

    /**
     * 测试徽章样式类
     */
    public function testGetBadgeClass(): void
    {
        $this->assertEquals('bg-danger', AnomalyStatus::DETECTED->getBadgeClass());
        $this->assertEquals('bg-warning', AnomalyStatus::ACTIVE->getBadgeClass());
        $this->assertEquals('bg-info', AnomalyStatus::INVESTIGATING->getBadgeClass());
        $this->assertEquals('bg-success', AnomalyStatus::RESOLVED->getBadgeClass());
        $this->assertEquals('bg-secondary', AnomalyStatus::IGNORED->getBadgeClass());
    }

    /**
     * 测试徽章标识
     */
    public function testGetBadge(): void
    {
        // getBadge() 应该返回与 getBadgeClass() 相同的值
        $this->assertEquals(AnomalyStatus::DETECTED->getBadgeClass(), AnomalyStatus::DETECTED->getBadge());
        $this->assertEquals(AnomalyStatus::ACTIVE->getBadgeClass(), AnomalyStatus::ACTIVE->getBadge());
        $this->assertEquals(AnomalyStatus::INVESTIGATING->getBadgeClass(), AnomalyStatus::INVESTIGATING->getBadge());
        $this->assertEquals(AnomalyStatus::RESOLVED->getBadgeClass(), AnomalyStatus::RESOLVED->getBadge());
        $this->assertEquals(AnomalyStatus::IGNORED->getBadgeClass(), AnomalyStatus::IGNORED->getBadge());
    }
}
