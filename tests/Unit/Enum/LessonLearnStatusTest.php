<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\LessonLearnStatus;

/**
 * LessonLearnStatus 枚举测试
 */
class LessonLearnStatusTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('not-buy', LessonLearnStatus::NOT_BUY->value);
        $this->assertEquals('pending', LessonLearnStatus::PENDING->value);
        $this->assertEquals('learning', LessonLearnStatus::LEARNING->value);
        $this->assertEquals('finished', LessonLearnStatus::FINISHED->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('未购买', LessonLearnStatus::NOT_BUY->getLabel());
        $this->assertEquals('未开始', LessonLearnStatus::PENDING->getLabel());
        $this->assertEquals('学习中', LessonLearnStatus::LEARNING->getLabel());
        $this->assertEquals('已完成', LessonLearnStatus::FINISHED->getLabel());
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = LessonLearnStatus::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(LessonLearnStatus::NOT_BUY, $cases);
        $this->assertContains(LessonLearnStatus::PENDING, $cases);
        $this->assertContains(LessonLearnStatus::LEARNING, $cases);
        $this->assertContains(LessonLearnStatus::FINISHED, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(LessonLearnStatus::NOT_BUY, LessonLearnStatus::from('not-buy'));
        $this->assertEquals(LessonLearnStatus::PENDING, LessonLearnStatus::from('pending'));
        $this->assertEquals(LessonLearnStatus::LEARNING, LessonLearnStatus::from('learning'));
        $this->assertEquals(LessonLearnStatus::FINISHED, LessonLearnStatus::from('finished'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(LessonLearnStatus::NOT_BUY, LessonLearnStatus::tryFrom('not-buy'));
        $this->assertEquals(LessonLearnStatus::PENDING, LessonLearnStatus::tryFrom('pending'));
        $this->assertEquals(LessonLearnStatus::LEARNING, LessonLearnStatus::tryFrom('learning'));
        $this->assertEquals(LessonLearnStatus::FINISHED, LessonLearnStatus::tryFrom('finished'));
        $this->assertNull(LessonLearnStatus::tryFrom('invalid_status'));
    }

    /**
     * 测试学习状态流转逻辑
     */
    public function test_status_flow(): void
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
            $this->assertInstanceOf(LessonLearnStatus::class, $status);
            $this->assertNotEmpty($status->getLabel());
        }
    }

    /**
     * 测试所有枚举值都有对应的标签
     */
    public function test_all_values_have_labels(): void
    {
        foreach (LessonLearnStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertNotEmpty($label, "LessonLearnStatus::{$status->name} should have a label");
        }
    }

    /**
     * 测试枚举值的唯一性
     */
    public function test_enum_values_are_unique(): void
    {
        $values = array_map(fn($case) => $case->value, LessonLearnStatus::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(count($values), $uniqueValues, 'All enum values should be unique');
    }

    /**
     * 测试枚举标签的唯一性
     */
    public function test_enum_labels_are_unique(): void
    {
        $labels = array_map(fn($case) => $case->getLabel(), LessonLearnStatus::cases());
        $uniqueLabels = array_unique($labels);
        
        $this->assertCount(count($labels), $uniqueLabels, 'All enum labels should be unique');
    }

    /**
     * 测试状态的逻辑关系
     */
    public function test_status_logic(): void
    {
        // 测试状态的先后顺序
        $notBuy = LessonLearnStatus::NOT_BUY;
        $pending = LessonLearnStatus::PENDING;
        $learning = LessonLearnStatus::LEARNING;
        $finished = LessonLearnStatus::FINISHED;
        
        // 验证每个状态都是不同的
        $this->assertNotEquals($notBuy, $pending);
        $this->assertNotEquals($pending, $learning);
        $this->assertNotEquals($learning, $finished);
        $this->assertNotEquals($notBuy, $finished);
    }
}