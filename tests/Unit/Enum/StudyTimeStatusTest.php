<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * StudyTimeStatus 枚举测试
 */
class StudyTimeStatusTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('valid', StudyTimeStatus::VALID->value);
        $this->assertEquals('invalid', StudyTimeStatus::INVALID->value);
        $this->assertEquals('pending', StudyTimeStatus::PENDING->value);
        $this->assertEquals('partial', StudyTimeStatus::PARTIAL->value);
        $this->assertEquals('excluded', StudyTimeStatus::EXCLUDED->value);
        $this->assertEquals('suspended', StudyTimeStatus::SUSPENDED->value);
        $this->assertEquals('reviewing', StudyTimeStatus::REVIEWING->value);
        $this->assertEquals('approved', StudyTimeStatus::APPROVED->value);
        $this->assertEquals('rejected', StudyTimeStatus::REJECTED->value);
        $this->assertEquals('expired', StudyTimeStatus::EXPIRED->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('有效学时', StudyTimeStatus::VALID->getLabel());
        $this->assertEquals('无效学时', StudyTimeStatus::INVALID->getLabel());
        $this->assertEquals('待确认学时', StudyTimeStatus::PENDING->getLabel());
        $this->assertEquals('部分有效学时', StudyTimeStatus::PARTIAL->getLabel());
        $this->assertEquals('已排除学时', StudyTimeStatus::EXCLUDED->getLabel());
        $this->assertEquals('暂停计时', StudyTimeStatus::SUSPENDED->getLabel());
        $this->assertEquals('审核中', StudyTimeStatus::REVIEWING->getLabel());
        $this->assertEquals('已认定', StudyTimeStatus::APPROVED->getLabel());
        $this->assertEquals('已拒绝', StudyTimeStatus::REJECTED->getLabel());
        $this->assertEquals('已过期', StudyTimeStatus::EXPIRED->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('符合学时认定标准的有效学习时长', StudyTimeStatus::VALID->getDescription());
        $this->assertEquals('不符合学时认定标准的无效学习时长', StudyTimeStatus::INVALID->getDescription());
        $this->assertEquals('等待系统或人工确认的学习时长', StudyTimeStatus::PENDING->getDescription());
        $this->assertEquals('部分时段有效的学习时长', StudyTimeStatus::PARTIAL->getDescription());
        $this->assertEquals('因特定原因被排除的学习时长', StudyTimeStatus::EXCLUDED->getDescription());
        $this->assertEquals('因异常情况暂停计时的时长', StudyTimeStatus::SUSPENDED->getDescription());
        $this->assertEquals('正在进行人工审核的学习时长', StudyTimeStatus::REVIEWING->getDescription());
        $this->assertEquals('已通过认定的最终有效学时', StudyTimeStatus::APPROVED->getDescription());
        $this->assertEquals('审核后被拒绝认定的学时', StudyTimeStatus::REJECTED->getDescription());
        $this->assertEquals('超过认定期限的过期学时', StudyTimeStatus::EXPIRED->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function test_get_color(): void
    {
        // 绿色状态
        $this->assertEquals('green', StudyTimeStatus::VALID->getColor());
        $this->assertEquals('green', StudyTimeStatus::APPROVED->getColor());
        
        // 红色状态
        $this->assertEquals('red', StudyTimeStatus::INVALID->getColor());
        $this->assertEquals('red', StudyTimeStatus::EXCLUDED->getColor());
        $this->assertEquals('red', StudyTimeStatus::REJECTED->getColor());
        
        // 橙色状态
        $this->assertEquals('orange', StudyTimeStatus::PENDING->getColor());
        $this->assertEquals('orange', StudyTimeStatus::REVIEWING->getColor());
        
        // 其他颜色
        $this->assertEquals('yellow', StudyTimeStatus::PARTIAL->getColor());
        $this->assertEquals('blue', StudyTimeStatus::SUSPENDED->getColor());
        $this->assertEquals('gray', StudyTimeStatus::EXPIRED->getColor());
    }

    /**
     * 测试获取图标
     */
    public function test_get_icon(): void
    {
        $this->assertEquals('check-circle', StudyTimeStatus::VALID->getIcon());
        $this->assertEquals('check-circle', StudyTimeStatus::APPROVED->getIcon());
        $this->assertEquals('x-circle', StudyTimeStatus::INVALID->getIcon());
        $this->assertEquals('x-circle', StudyTimeStatus::EXCLUDED->getIcon());
        $this->assertEquals('x-circle', StudyTimeStatus::REJECTED->getIcon());
        $this->assertEquals('clock', StudyTimeStatus::PENDING->getIcon());
        $this->assertEquals('pie-chart', StudyTimeStatus::PARTIAL->getIcon());
        $this->assertEquals('pause', StudyTimeStatus::SUSPENDED->getIcon());
        $this->assertEquals('search', StudyTimeStatus::REVIEWING->getIcon());
        $this->assertEquals('calendar-x', StudyTimeStatus::EXPIRED->getIcon());
    }

    /**
     * 测试是否为最终状态
     */
    public function test_is_final(): void
    {
        // 最终状态
        $this->assertTrue(StudyTimeStatus::APPROVED->isFinal());
        $this->assertTrue(StudyTimeStatus::REJECTED->isFinal());
        $this->assertTrue(StudyTimeStatus::EXPIRED->isFinal());
        
        // 非最终状态
        $this->assertFalse(StudyTimeStatus::VALID->isFinal());
        $this->assertFalse(StudyTimeStatus::INVALID->isFinal());
        $this->assertFalse(StudyTimeStatus::PENDING->isFinal());
        $this->assertFalse(StudyTimeStatus::PARTIAL->isFinal());
        $this->assertFalse(StudyTimeStatus::EXCLUDED->isFinal());
        $this->assertFalse(StudyTimeStatus::SUSPENDED->isFinal());
        $this->assertFalse(StudyTimeStatus::REVIEWING->isFinal());
    }

    /**
     * 测试是否可以计入有效学时
     */
    public function test_is_countable(): void
    {
        // 可计入
        $this->assertTrue(StudyTimeStatus::VALID->isCountable());
        $this->assertTrue(StudyTimeStatus::PARTIAL->isCountable());
        $this->assertTrue(StudyTimeStatus::APPROVED->isCountable());
        
        // 不可计入
        $this->assertFalse(StudyTimeStatus::INVALID->isCountable());
        $this->assertFalse(StudyTimeStatus::PENDING->isCountable());
        $this->assertFalse(StudyTimeStatus::EXCLUDED->isCountable());
        $this->assertFalse(StudyTimeStatus::SUSPENDED->isCountable());
        $this->assertFalse(StudyTimeStatus::REVIEWING->isCountable());
        $this->assertFalse(StudyTimeStatus::REJECTED->isCountable());
        $this->assertFalse(StudyTimeStatus::EXPIRED->isCountable());
    }

    /**
     * 测试是否需要人工审核
     */
    public function test_needs_review(): void
    {
        // 需要审核
        $this->assertTrue(StudyTimeStatus::PENDING->needsReview());
        $this->assertTrue(StudyTimeStatus::REVIEWING->needsReview());
        
        // 不需要审核
        $this->assertFalse(StudyTimeStatus::VALID->needsReview());
        $this->assertFalse(StudyTimeStatus::INVALID->needsReview());
        $this->assertFalse(StudyTimeStatus::PARTIAL->needsReview());
        $this->assertFalse(StudyTimeStatus::EXCLUDED->needsReview());
        $this->assertFalse(StudyTimeStatus::SUSPENDED->needsReview());
        $this->assertFalse(StudyTimeStatus::APPROVED->needsReview());
        $this->assertFalse(StudyTimeStatus::REJECTED->needsReview());
        $this->assertFalse(StudyTimeStatus::EXPIRED->needsReview());
    }

    /**
     * 测试是否可以修改
     */
    public function test_is_modifiable(): void
    {
        // 可修改（非最终状态）
        $this->assertTrue(StudyTimeStatus::VALID->isModifiable());
        $this->assertTrue(StudyTimeStatus::INVALID->isModifiable());
        $this->assertTrue(StudyTimeStatus::PENDING->isModifiable());
        $this->assertTrue(StudyTimeStatus::PARTIAL->isModifiable());
        $this->assertTrue(StudyTimeStatus::EXCLUDED->isModifiable());
        $this->assertTrue(StudyTimeStatus::SUSPENDED->isModifiable());
        $this->assertTrue(StudyTimeStatus::REVIEWING->isModifiable());
        
        // 不可修改（最终状态）
        $this->assertFalse(StudyTimeStatus::APPROVED->isModifiable());
        $this->assertFalse(StudyTimeStatus::REJECTED->isModifiable());
        $this->assertFalse(StudyTimeStatus::EXPIRED->isModifiable());
    }

    /**
     * 测试是否需要提醒学员
     */
    public function test_requires_notification(): void
    {
        // 需要提醒
        $this->assertTrue(StudyTimeStatus::INVALID->requiresNotification());
        $this->assertTrue(StudyTimeStatus::EXCLUDED->requiresNotification());
        $this->assertTrue(StudyTimeStatus::SUSPENDED->requiresNotification());
        $this->assertTrue(StudyTimeStatus::REJECTED->requiresNotification());
        $this->assertTrue(StudyTimeStatus::EXPIRED->requiresNotification());
        
        // 不需要提醒
        $this->assertFalse(StudyTimeStatus::VALID->requiresNotification());
        $this->assertFalse(StudyTimeStatus::PENDING->requiresNotification());
        $this->assertFalse(StudyTimeStatus::PARTIAL->requiresNotification());
        $this->assertFalse(StudyTimeStatus::REVIEWING->requiresNotification());
        $this->assertFalse(StudyTimeStatus::APPROVED->requiresNotification());
    }

    /**
     * 测试获取下一个可能的状态
     */
    public function test_get_next_possible_statuses(): void
    {
        // PENDING 可以转换到的状态
        $pending = StudyTimeStatus::PENDING->getNextPossibleStatuses();
        $this->assertCount(4, $pending);
        $this->assertContains(StudyTimeStatus::VALID, $pending);
        $this->assertContains(StudyTimeStatus::INVALID, $pending);
        $this->assertContains(StudyTimeStatus::PARTIAL, $pending);
        $this->assertContains(StudyTimeStatus::REVIEWING, $pending);
        
        // REVIEWING 可以转换到的状态
        $reviewing = StudyTimeStatus::REVIEWING->getNextPossibleStatuses();
        $this->assertCount(3, $reviewing);
        $this->assertContains(StudyTimeStatus::APPROVED, $reviewing);
        $this->assertContains(StudyTimeStatus::REJECTED, $reviewing);
        $this->assertContains(StudyTimeStatus::PARTIAL, $reviewing);
        
        // 最终状态不能转换
        $this->assertCount(0, StudyTimeStatus::APPROVED->getNextPossibleStatuses());
        $this->assertCount(0, StudyTimeStatus::REJECTED->getNextPossibleStatuses());
        $this->assertCount(0, StudyTimeStatus::EXPIRED->getNextPossibleStatuses());
    }

    /**
     * 测试状态转换检查
     */
    public function test_can_transition_to(): void
    {
        // PENDING 的转换
        $this->assertTrue(StudyTimeStatus::PENDING->canTransitionTo(StudyTimeStatus::VALID));
        $this->assertTrue(StudyTimeStatus::PENDING->canTransitionTo(StudyTimeStatus::INVALID));
        $this->assertFalse(StudyTimeStatus::PENDING->canTransitionTo(StudyTimeStatus::APPROVED));
        
        // REVIEWING 的转换
        $this->assertTrue(StudyTimeStatus::REVIEWING->canTransitionTo(StudyTimeStatus::APPROVED));
        $this->assertTrue(StudyTimeStatus::REVIEWING->canTransitionTo(StudyTimeStatus::REJECTED));
        $this->assertFalse(StudyTimeStatus::REVIEWING->canTransitionTo(StudyTimeStatus::VALID));
        
        // 最终状态不能转换
        $this->assertFalse(StudyTimeStatus::APPROVED->canTransitionTo(StudyTimeStatus::VALID));
        $this->assertFalse(StudyTimeStatus::REJECTED->canTransitionTo(StudyTimeStatus::PENDING));
    }

    /**
     * 测试获取所有状态
     */
    public function test_get_all_statuses(): void
    {
        $statuses = StudyTimeStatus::getAllStatuses();
        
        $this->assertCount(10, $statuses);
        $this->assertContains(StudyTimeStatus::VALID, $statuses);
        $this->assertContains(StudyTimeStatus::INVALID, $statuses);
        $this->assertContains(StudyTimeStatus::PENDING, $statuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $statuses);
        $this->assertContains(StudyTimeStatus::EXCLUDED, $statuses);
        $this->assertContains(StudyTimeStatus::SUSPENDED, $statuses);
        $this->assertContains(StudyTimeStatus::REVIEWING, $statuses);
        $this->assertContains(StudyTimeStatus::APPROVED, $statuses);
        $this->assertContains(StudyTimeStatus::REJECTED, $statuses);
        $this->assertContains(StudyTimeStatus::EXPIRED, $statuses);
    }

    /**
     * 测试获取活跃状态
     */
    public function test_get_active_statuses(): void
    {
        $statuses = StudyTimeStatus::getActiveStatuses();
        
        $this->assertCount(7, $statuses); // 10 - 3 个最终状态
        $this->assertContains(StudyTimeStatus::VALID, $statuses);
        $this->assertContains(StudyTimeStatus::PENDING, $statuses);
        $this->assertNotContains(StudyTimeStatus::APPROVED, $statuses);
        $this->assertNotContains(StudyTimeStatus::REJECTED, $statuses);
        $this->assertNotContains(StudyTimeStatus::EXPIRED, $statuses);
    }

    /**
     * 测试获取最终状态
     */
    public function test_get_final_statuses(): void
    {
        $statuses = StudyTimeStatus::getFinalStatuses();
        
        $this->assertCount(3, $statuses);
        $this->assertContains(StudyTimeStatus::APPROVED, $statuses);
        $this->assertContains(StudyTimeStatus::REJECTED, $statuses);
        $this->assertContains(StudyTimeStatus::EXPIRED, $statuses);
    }

    /**
     * 测试获取可计入学时的状态
     */
    public function test_get_countable_statuses(): void
    {
        $statuses = StudyTimeStatus::getCountableStatuses();
        
        $this->assertCount(3, $statuses);
        $this->assertContains(StudyTimeStatus::VALID, $statuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $statuses);
        $this->assertContains(StudyTimeStatus::APPROVED, $statuses);
    }

    /**
     * 测试获取需要审核的状态
     */
    public function test_get_review_statuses(): void
    {
        $statuses = StudyTimeStatus::getReviewStatuses();
        
        $this->assertCount(2, $statuses);
        $this->assertContains(StudyTimeStatus::PENDING, $statuses);
        $this->assertContains(StudyTimeStatus::REVIEWING, $statuses);
    }

    /**
     * 测试从字符串创建
     */
    public function test_from_string(): void
    {
        // 英文
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::fromString('valid'));
        $this->assertEquals(StudyTimeStatus::INVALID, StudyTimeStatus::fromString('invalid'));
        $this->assertEquals(StudyTimeStatus::PENDING, StudyTimeStatus::fromString('pending'));
        $this->assertEquals(StudyTimeStatus::APPROVED, StudyTimeStatus::fromString('approved'));
        
        // 中文
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::fromString('有效'));
        $this->assertEquals(StudyTimeStatus::INVALID, StudyTimeStatus::fromString('无效'));
        $this->assertEquals(StudyTimeStatus::PENDING, StudyTimeStatus::fromString('待确认'));
        $this->assertEquals(StudyTimeStatus::APPROVED, StudyTimeStatus::fromString('已认定'));
        
        // 大写
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::fromString('VALID'));
        
        // 无效值
        $this->assertNull(StudyTimeStatus::fromString('invalid_status'));
        $this->assertNull(StudyTimeStatus::fromString(''));
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = StudyTimeStatus::cases();
        
        $this->assertCount(10, $cases);
        foreach ($cases as $case) {
            $this->assertInstanceOf(StudyTimeStatus::class, $case);
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::from('valid'));
        $this->assertEquals(StudyTimeStatus::APPROVED, StudyTimeStatus::from('approved'));
        $this->assertEquals(StudyTimeStatus::EXPIRED, StudyTimeStatus::from('expired'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::tryFrom('valid'));
        $this->assertEquals(StudyTimeStatus::REJECTED, StudyTimeStatus::tryFrom('rejected'));
        $this->assertNull(StudyTimeStatus::tryFrom('invalid_value'));
    }
}