<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

/**
 * BehaviorType 枚举测试
 */
class BehaviorTypeTest extends TestCase
{
    /**
     * 测试视频控制行为的枚举值
     */
    public function test_video_control_behavior_values(): void
    {
        $this->assertEquals('play', BehaviorType::PLAY->value);
        $this->assertEquals('pause', BehaviorType::PAUSE->value);
        $this->assertEquals('seek', BehaviorType::SEEK->value);
        $this->assertEquals('volume_change', BehaviorType::VOLUME_CHANGE->value);
        $this->assertEquals('speed_change', BehaviorType::SPEED_CHANGE->value);
        $this->assertEquals('fullscreen_enter', BehaviorType::FULLSCREEN_ENTER->value);
        $this->assertEquals('fullscreen_exit', BehaviorType::FULLSCREEN_EXIT->value);
    }

    /**
     * 测试窗口焦点行为的枚举值
     */
    public function test_window_focus_behavior_values(): void
    {
        $this->assertEquals('window_focus', BehaviorType::WINDOW_FOCUS->value);
        $this->assertEquals('window_blur', BehaviorType::WINDOW_BLUR->value);
        $this->assertEquals('page_visible', BehaviorType::PAGE_VISIBLE->value);
        $this->assertEquals('page_hidden', BehaviorType::PAGE_HIDDEN->value);
    }

    /**
     * 测试鼠标行为的枚举值
     */
    public function test_mouse_behavior_values(): void
    {
        $this->assertEquals('mouse_enter', BehaviorType::MOUSE_ENTER->value);
        $this->assertEquals('mouse_leave', BehaviorType::MOUSE_LEAVE->value);
        $this->assertEquals('mouse_move', BehaviorType::MOUSE_MOVE->value);
        $this->assertEquals('mouse_click', BehaviorType::MOUSE_CLICK->value);
    }

    /**
     * 测试异常行为的枚举值
     */
    public function test_suspicious_behavior_values(): void
    {
        $this->assertEquals('rapid_seek', BehaviorType::RAPID_SEEK->value);
        $this->assertEquals('multiple_tab', BehaviorType::MULTIPLE_TAB->value);
        $this->assertEquals('developer_tools', BehaviorType::DEVELOPER_TOOLS->value);
        $this->assertEquals('copy_attempt', BehaviorType::COPY_ATTEMPT->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        // 视频控制
        $this->assertEquals('播放', BehaviorType::PLAY->getLabel());
        $this->assertEquals('暂停', BehaviorType::PAUSE->getLabel());
        $this->assertEquals('拖拽进度', BehaviorType::SEEK->getLabel());
        
        // 窗口焦点
        $this->assertEquals('窗口获得焦点', BehaviorType::WINDOW_FOCUS->getLabel());
        $this->assertEquals('窗口失去焦点', BehaviorType::WINDOW_BLUR->getLabel());
        
        // 鼠标行为
        $this->assertEquals('鼠标点击', BehaviorType::MOUSE_CLICK->getLabel());
        $this->assertEquals('鼠标移动', BehaviorType::MOUSE_MOVE->getLabel());
        
        // 异常行为
        $this->assertEquals('快速拖拽', BehaviorType::RAPID_SEEK->getLabel());
        $this->assertEquals('开发者工具', BehaviorType::DEVELOPER_TOOLS->getLabel());
    }

    /**
     * 测试是否为可疑行为
     */
    public function test_is_suspicious(): void
    {
        // 可疑行为
        $this->assertTrue(BehaviorType::RAPID_SEEK->isSuspicious());
        $this->assertTrue(BehaviorType::MULTIPLE_TAB->isSuspicious());
        $this->assertTrue(BehaviorType::DEVELOPER_TOOLS->isSuspicious());
        $this->assertTrue(BehaviorType::COPY_ATTEMPT->isSuspicious());
        
        // 正常行为
        $this->assertFalse(BehaviorType::PLAY->isSuspicious());
        $this->assertFalse(BehaviorType::PAUSE->isSuspicious());
        $this->assertFalse(BehaviorType::MOUSE_CLICK->isSuspicious());
        $this->assertFalse(BehaviorType::WINDOW_FOCUS->isSuspicious());
        $this->assertFalse(BehaviorType::IDLE_START->isSuspicious());
    }

    /**
     * 测试获取行为分类
     */
    public function test_get_category(): void
    {
        // 视频控制
        $this->assertEquals('video_control', BehaviorType::PLAY->getCategory());
        $this->assertEquals('video_control', BehaviorType::PAUSE->getCategory());
        $this->assertEquals('video_control', BehaviorType::SEEK->getCategory());
        $this->assertEquals('video_control', BehaviorType::VOLUME_CHANGE->getCategory());
        $this->assertEquals('video_control', BehaviorType::SPEED_CHANGE->getCategory());
        $this->assertEquals('video_control', BehaviorType::FULLSCREEN_ENTER->getCategory());
        $this->assertEquals('video_control', BehaviorType::FULLSCREEN_EXIT->getCategory());
        
        // 窗口焦点
        $this->assertEquals('window_focus', BehaviorType::WINDOW_FOCUS->getCategory());
        $this->assertEquals('window_focus', BehaviorType::WINDOW_BLUR->getCategory());
        $this->assertEquals('window_focus', BehaviorType::PAGE_VISIBLE->getCategory());
        $this->assertEquals('window_focus', BehaviorType::PAGE_HIDDEN->getCategory());
        
        // 鼠标活动
        $this->assertEquals('mouse_activity', BehaviorType::MOUSE_ENTER->getCategory());
        $this->assertEquals('mouse_activity', BehaviorType::MOUSE_LEAVE->getCategory());
        $this->assertEquals('mouse_activity', BehaviorType::MOUSE_MOVE->getCategory());
        $this->assertEquals('mouse_activity', BehaviorType::MOUSE_CLICK->getCategory());
        
        // 键盘活动
        $this->assertEquals('keyboard_activity', BehaviorType::KEY_PRESS->getCategory());
        $this->assertEquals('keyboard_activity', BehaviorType::KEY_COMBINATION->getCategory());
        
        // 空闲检测
        $this->assertEquals('idle_detection', BehaviorType::IDLE_START->getCategory());
        $this->assertEquals('idle_detection', BehaviorType::IDLE_END->getCategory());
        $this->assertEquals('idle_detection', BehaviorType::ACTIVITY_DETECTED->getCategory());
        
        // 网络状态
        $this->assertEquals('network_status', BehaviorType::NETWORK_ONLINE->getCategory());
        $this->assertEquals('network_status', BehaviorType::NETWORK_OFFLINE->getCategory());
        $this->assertEquals('network_status', BehaviorType::CONNECTION_SLOW->getCategory());
        
        // 设备状态
        $this->assertEquals('device_status', BehaviorType::DEVICE_ORIENTATION_CHANGE->getCategory());
        $this->assertEquals('device_status', BehaviorType::SCREEN_RESIZE->getCategory());
        
        // 可疑行为
        $this->assertEquals('suspicious_behavior', BehaviorType::RAPID_SEEK->getCategory());
        $this->assertEquals('suspicious_behavior', BehaviorType::MULTIPLE_TAB->getCategory());
        $this->assertEquals('suspicious_behavior', BehaviorType::DEVELOPER_TOOLS->getCategory());
        $this->assertEquals('suspicious_behavior', BehaviorType::COPY_ATTEMPT->getCategory());
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = BehaviorType::cases();
        
        $this->assertGreaterThan(20, count($cases)); // 至少有20个以上的行为类型
        
        // 检查一些关键的行为类型是否存在
        $this->assertContains(BehaviorType::PLAY, $cases);
        $this->assertContains(BehaviorType::PAUSE, $cases);
        $this->assertContains(BehaviorType::MOUSE_CLICK, $cases);
        $this->assertContains(BehaviorType::WINDOW_FOCUS, $cases);
        $this->assertContains(BehaviorType::RAPID_SEEK, $cases);
        $this->assertContains(BehaviorType::DEVELOPER_TOOLS, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(BehaviorType::PLAY, BehaviorType::from('play'));
        $this->assertEquals(BehaviorType::PAUSE, BehaviorType::from('pause'));
        $this->assertEquals(BehaviorType::MOUSE_CLICK, BehaviorType::from('mouse_click'));
        $this->assertEquals(BehaviorType::RAPID_SEEK, BehaviorType::from('rapid_seek'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(BehaviorType::PLAY, BehaviorType::tryFrom('play'));
        $this->assertEquals(BehaviorType::DEVELOPER_TOOLS, BehaviorType::tryFrom('developer_tools'));
        $this->assertNull(BehaviorType::tryFrom('invalid_behavior'));
    }

    /**
     * 测试分类分组
     */
    public function test_category_grouping(): void
    {
        $allCases = BehaviorType::cases();
        $categories = [];
        
        foreach ($allCases as $case) {
            $category = $case->getCategory();
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $case;
        }
        
        // 确保所有分类都有成员
        $this->assertArrayHasKey('video_control', $categories);
        $this->assertArrayHasKey('window_focus', $categories);
        $this->assertArrayHasKey('mouse_activity', $categories);
        $this->assertArrayHasKey('keyboard_activity', $categories);
        $this->assertArrayHasKey('idle_detection', $categories);
        $this->assertArrayHasKey('network_status', $categories);
        $this->assertArrayHasKey('device_status', $categories);
        $this->assertArrayHasKey('suspicious_behavior', $categories);
        
        // 检查每个分类至少有一个成员
        foreach ($categories as $category => $members) {
            $this->assertNotEmpty($members, "Category '{$category}' should have at least one member");
        }
    }
}