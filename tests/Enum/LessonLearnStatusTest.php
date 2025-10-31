<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\LessonLearnStatus;

/**
 * LessonLearnStatus 枚举测试
 *
 * @internal
 */
#[CoversClass(LessonLearnStatus::class)]
#[RunTestsInSeparateProcesses]
final class LessonLearnStatusTest extends AbstractEnumTestCase
{
    #[TestWith([LessonLearnStatus::NOT_BUY, 'not-buy', '未购买'])]
    #[TestWith([LessonLearnStatus::PENDING, 'pending', '未开始'])]
    #[TestWith([LessonLearnStatus::LEARNING, 'learning', '学习中'])]
    #[TestWith([LessonLearnStatus::FINISHED, 'finished', '已完成'])]
    public function testValueAndLabel(LessonLearnStatus $enum, string $expectedValue, string $expectedLabel): void
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
        $result = LessonLearnStatus::PENDING->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('pending', $result['value']);
        $this->assertEquals('未开始', $result['label']);
    }

    /**
     * 测试枚举 cases
     */
    public function testCases(): void
    {
        $cases = LessonLearnStatus::cases();

        self::assertCount(4, $cases);
        self::assertContains(LessonLearnStatus::NOT_BUY, $cases);
        self::assertContains(LessonLearnStatus::PENDING, $cases);
        self::assertContains(LessonLearnStatus::LEARNING, $cases);
        self::assertContains(LessonLearnStatus::FINISHED, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function testFrom(): void
    {
        self::assertSame(LessonLearnStatus::NOT_BUY, LessonLearnStatus::from('not-buy'));
        self::assertSame(LessonLearnStatus::PENDING, LessonLearnStatus::from('pending'));
        self::assertSame(LessonLearnStatus::LEARNING, LessonLearnStatus::from('learning'));
        self::assertSame(LessonLearnStatus::FINISHED, LessonLearnStatus::from('finished'));
    }

    /**
     * 测试 tryFrom
     */
    public function testTryFrom(): void
    {
        self::assertSame(LessonLearnStatus::NOT_BUY, LessonLearnStatus::tryFrom('not-buy'));
        self::assertSame(LessonLearnStatus::PENDING, LessonLearnStatus::tryFrom('pending'));
        self::assertSame(LessonLearnStatus::LEARNING, LessonLearnStatus::tryFrom('learning'));
        self::assertSame(LessonLearnStatus::FINISHED, LessonLearnStatus::tryFrom('finished'));
        self::assertNull(LessonLearnStatus::tryFrom('invalid_status'));
    }

    /**
     * 测试学习状态流转逻辑
     */
    public function testStatusFlow(): void
    {
        // 测试典型的状态流转
        $typicalFlow = [
            LessonLearnStatus::NOT_BUY,  // 未购买
            LessonLearnStatus::PENDING,  // 购买后未开始
            LessonLearnStatus::LEARNING, // 开始学习
            LessonLearnStatus::FINISHED, // 完成学习
        ];

        // 验证流程中的每个状态都有对应的枚举
        foreach ($typicalFlow as $status) {
            self::assertNotEmpty($status->getLabel());
        }
    }

    /**
     * 测试所有枚举值都有对应的标签
     */
    public function testAllValuesHaveLabels(): void
    {
        foreach (LessonLearnStatus::cases() as $status) {
            $label = $status->getLabel();
            self::assertNotEmpty($label, "LessonLearnStatus::{$status->name} should have a label");
        }
    }

    /**
     * 测试枚举值的唯一性
     */
    public function testEnumValuesAreUnique(): void
    {
        $values = array_map(fn ($case) => $case->value, LessonLearnStatus::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values should be unique');
    }

    /**
     * 测试枚举标签的唯一性
     */
    public function testEnumLabelsAreUnique(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), LessonLearnStatus::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels should be unique');
    }

    /**
     * 测试状态的逻辑关系
     */
    public function testStatusLogic(): void
    {
        // 测试状态的先后顺序
        $notBuy = LessonLearnStatus::NOT_BUY;
        $pending = LessonLearnStatus::PENDING;
        $learning = LessonLearnStatus::LEARNING;
        $finished = LessonLearnStatus::FINISHED;

        // 验证每个状态都是不同的
        self::assertNotEquals($notBuy, $pending);
        self::assertNotEquals($pending, $learning);
        self::assertNotEquals($learning, $finished);
        self::assertNotEquals($notBuy, $finished);
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (LessonLearnStatus $case) => $case->value, LessonLearnStatus::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'Enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (LessonLearnStatus $case) => $case->getLabel(), LessonLearnStatus::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'Enum labels must be unique');
    }
}
