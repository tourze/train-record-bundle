<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;

/**
 * AnomalyStatus 枚举测试
 */
class AnomalyStatusTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('detected', AnomalyStatus::DETECTED->value);
        $this->assertEquals('investigating', AnomalyStatus::INVESTIGATING->value);
        $this->assertEquals('resolved', AnomalyStatus::RESOLVED->value);
        $this->assertEquals('ignored', AnomalyStatus::IGNORED->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('已检测', AnomalyStatus::DETECTED->getLabel());
        $this->assertEquals('调查中', AnomalyStatus::INVESTIGATING->getLabel());
        $this->assertEquals('已解决', AnomalyStatus::RESOLVED->getLabel());
        $this->assertEquals('已忽略', AnomalyStatus::IGNORED->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('异常已被检测到，等待处理', AnomalyStatus::DETECTED->getDescription());
        $this->assertEquals('异常正在调查处理中', AnomalyStatus::INVESTIGATING->getDescription());
        $this->assertEquals('异常已被成功解决', AnomalyStatus::RESOLVED->getDescription());
        $this->assertEquals('异常被标记为忽略', AnomalyStatus::IGNORED->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function test_get_color(): void
    {
        $this->assertEquals('red', AnomalyStatus::DETECTED->getColor());
        $this->assertEquals('orange', AnomalyStatus::INVESTIGATING->getColor());
        $this->assertEquals('green', AnomalyStatus::RESOLVED->getColor());
        $this->assertEquals('gray', AnomalyStatus::IGNORED->getColor());
    }

    /**
     * 测试获取图标
     */
    public function test_get_icon(): void
    {
        $this->assertEquals('warning', AnomalyStatus::DETECTED->getIcon());
        $this->assertEquals('search', AnomalyStatus::INVESTIGATING->getIcon());
        $this->assertEquals('check', AnomalyStatus::RESOLVED->getIcon());
        $this->assertEquals('minus', AnomalyStatus::IGNORED->getIcon());
    }

    /**
     * 测试是否为活跃状态
     */
    public function test_is_active(): void
    {
        $this->assertTrue(AnomalyStatus::DETECTED->isActive());
        $this->assertTrue(AnomalyStatus::INVESTIGATING->isActive());
        $this->assertFalse(AnomalyStatus::RESOLVED->isActive());
        $this->assertFalse(AnomalyStatus::IGNORED->isActive());
    }

    /**
     * 测试是否已完成处理
     */
    public function test_is_completed(): void
    {
        $this->assertFalse(AnomalyStatus::DETECTED->isCompleted());
        $this->assertFalse(AnomalyStatus::INVESTIGATING->isCompleted());
        $this->assertTrue(AnomalyStatus::RESOLVED->isCompleted());
        $this->assertTrue(AnomalyStatus::IGNORED->isCompleted());
    }

    /**
     * 测试是否需要处理
     */
    public function test_needs_processing(): void
    {
        $this->assertTrue(AnomalyStatus::DETECTED->needsProcessing());
        $this->assertFalse(AnomalyStatus::INVESTIGATING->needsProcessing());
        $this->assertFalse(AnomalyStatus::RESOLVED->needsProcessing());
        $this->assertFalse(AnomalyStatus::IGNORED->needsProcessing());
    }

    /**
     * 测试是否正在处理
     */
    public function test_is_processing(): void
    {
        $this->assertFalse(AnomalyStatus::DETECTED->isProcessing());
        $this->assertTrue(AnomalyStatus::INVESTIGATING->isProcessing());
        $this->assertFalse(AnomalyStatus::RESOLVED->isProcessing());
        $this->assertFalse(AnomalyStatus::IGNORED->isProcessing());
    }

    /**
     * 测试获取下一个可能的状态
     */
    public function test_get_next_possible_statuses(): void
    {
        $detected = AnomalyStatus::DETECTED->getNextPossibleStatuses();
        $this->assertCount(3, $detected);
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
    public function test_can_transition_to(): void
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
    public function test_get_all_statuses(): void
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
    public function test_get_active_statuses(): void
    {
        $statuses = AnomalyStatus::getActiveStatuses();
        
        $this->assertCount(2, $statuses);
        $this->assertContains(AnomalyStatus::DETECTED, $statuses);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $statuses);
    }

    /**
     * 测试获取已完成状态
     */
    public function test_get_completed_statuses(): void
    {
        $statuses = AnomalyStatus::getCompletedStatuses();
        
        $this->assertCount(2, $statuses);
        $this->assertContains(AnomalyStatus::RESOLVED, $statuses);
        $this->assertContains(AnomalyStatus::IGNORED, $statuses);
    }

    /**
     * 测试从字符串创建
     */
    public function test_from_string(): void
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

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = AnomalyStatus::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(AnomalyStatus::DETECTED, $cases);
        $this->assertContains(AnomalyStatus::INVESTIGATING, $cases);
        $this->assertContains(AnomalyStatus::RESOLVED, $cases);
        $this->assertContains(AnomalyStatus::IGNORED, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(AnomalyStatus::DETECTED, AnomalyStatus::from('detected'));
        $this->assertEquals(AnomalyStatus::INVESTIGATING, AnomalyStatus::from('investigating'));
        $this->assertEquals(AnomalyStatus::RESOLVED, AnomalyStatus::from('resolved'));
        $this->assertEquals(AnomalyStatus::IGNORED, AnomalyStatus::from('ignored'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(AnomalyStatus::DETECTED, AnomalyStatus::tryFrom('detected'));
        $this->assertEquals(AnomalyStatus::RESOLVED, AnomalyStatus::tryFrom('resolved'));
        $this->assertNull(AnomalyStatus::tryFrom('invalid'));
    }
}