<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\LearnAction;

/**
 * LearnAction 枚举测试
 */
class LearnActionTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('start', LearnAction::START->value);
        $this->assertEquals('play', LearnAction::PLAY->value);
        $this->assertEquals('pause', LearnAction::PAUSE->value);
        $this->assertEquals('watch', LearnAction::WATCH->value);
        $this->assertEquals('ended', LearnAction::ENDED->value);
        $this->assertEquals('practice', LearnAction::PRACTICE->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('开始学习', LearnAction::START->getLabel());
        $this->assertEquals('播放', LearnAction::PLAY->getLabel());
        $this->assertEquals('暂停', LearnAction::PAUSE->getLabel());
        $this->assertEquals('观看', LearnAction::WATCH->getLabel());
        $this->assertEquals('看完', LearnAction::ENDED->getLabel());
        $this->assertEquals('练习', LearnAction::PRACTICE->getLabel());
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = LearnAction::cases();
        
        $this->assertCount(6, $cases);
        $this->assertContains(LearnAction::START, $cases);
        $this->assertContains(LearnAction::PLAY, $cases);
        $this->assertContains(LearnAction::PAUSE, $cases);
        $this->assertContains(LearnAction::WATCH, $cases);
        $this->assertContains(LearnAction::ENDED, $cases);
        $this->assertContains(LearnAction::PRACTICE, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(LearnAction::START, LearnAction::from('start'));
        $this->assertEquals(LearnAction::PLAY, LearnAction::from('play'));
        $this->assertEquals(LearnAction::PAUSE, LearnAction::from('pause'));
        $this->assertEquals(LearnAction::WATCH, LearnAction::from('watch'));
        $this->assertEquals(LearnAction::ENDED, LearnAction::from('ended'));
        $this->assertEquals(LearnAction::PRACTICE, LearnAction::from('practice'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(LearnAction::START, LearnAction::tryFrom('start'));
        $this->assertEquals(LearnAction::PLAY, LearnAction::tryFrom('play'));
        $this->assertEquals(LearnAction::ENDED, LearnAction::tryFrom('ended'));
        $this->assertNull(LearnAction::tryFrom('invalid_action'));
    }

    /**
     * 测试学习流程逻辑
     */
    public function test_learning_flow(): void
    {
        // 测试典型的学习流程
        $typicalFlow = [
            LearnAction::START,    // 开始学习
            LearnAction::PLAY,     // 播放视频
            LearnAction::PAUSE,    // 暂停
            LearnAction::PLAY,     // 继续播放
            LearnAction::ENDED,    // 看完
            LearnAction::PRACTICE, // 练习
        ];
        
        // 验证流程中的每个动作都有对应的枚举
        foreach ($typicalFlow as $action) {
            $this->assertInstanceOf(LearnAction::class, $action);
            $this->assertNotEmpty($action->getLabel());
        }
    }

    /**
     * 测试所有枚举值都有对应的标签
     */
    public function test_all_values_have_labels(): void
    {
        foreach (LearnAction::cases() as $action) {
            $label = $action->getLabel();
            $this->assertNotEmpty($label, "LearnAction::{$action->name} should have a label");
        }
    }

    /**
     * 测试枚举值的唯一性
     */
    public function test_enum_values_are_unique(): void
    {
        $values = array_map(fn($case) => $case->value, LearnAction::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(count($values), $uniqueValues, 'All enum values should be unique');
    }

    /**
     * 测试枚举标签的唯一性
     */
    public function test_enum_labels_are_unique(): void
    {
        $labels = array_map(fn($case) => $case->getLabel(), LearnAction::cases());
        $uniqueLabels = array_unique($labels);
        
        $this->assertCount(count($labels), $uniqueLabels, 'All enum labels should be unique');
    }
}