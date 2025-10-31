<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

/**
 * BehaviorType 枚举测试
 *
 * @internal
 */
#[CoversClass(BehaviorType::class)]
#[RunTestsInSeparateProcesses]
final class BehaviorTypeTest extends AbstractEnumTestCase
{
    #[TestWith([BehaviorType::PLAY, 'play', '播放'])]
    #[TestWith([BehaviorType::PAUSE, 'pause', '暂停'])]
    #[TestWith([BehaviorType::STOP, 'stop', '停止'])]
    #[TestWith([BehaviorType::SEEK, 'seek', '拖拽进度'])]
    #[TestWith([BehaviorType::FAST_FORWARD, 'fast_forward', '快进'])]
    #[TestWith([BehaviorType::VOLUME_CHANGE, 'volume_change', '音量调节'])]
    #[TestWith([BehaviorType::SPEED_CHANGE, 'speed_change', '倍速调节'])]
    #[TestWith([BehaviorType::FULLSCREEN_ENTER, 'fullscreen_enter', '进入全屏'])]
    #[TestWith([BehaviorType::FULLSCREEN_EXIT, 'fullscreen_exit', '退出全屏'])]
    #[TestWith([BehaviorType::WINDOW_FOCUS, 'window_focus', '窗口获得焦点'])]
    #[TestWith([BehaviorType::WINDOW_BLUR, 'window_blur', '窗口失去焦点'])]
    #[TestWith([BehaviorType::PAGE_VISIBLE, 'page_visible', '页面可见'])]
    #[TestWith([BehaviorType::PAGE_HIDDEN, 'page_hidden', '页面隐藏'])]
    #[TestWith([BehaviorType::MOUSE_ENTER, 'mouse_enter', '鼠标进入'])]
    #[TestWith([BehaviorType::MOUSE_LEAVE, 'mouse_leave', '鼠标离开'])]
    #[TestWith([BehaviorType::MOUSE_MOVE, 'mouse_move', '鼠标移动'])]
    #[TestWith([BehaviorType::MOUSE_CLICK, 'mouse_click', '鼠标点击'])]
    #[TestWith([BehaviorType::KEY_PRESS, 'key_press', '按键'])]
    #[TestWith([BehaviorType::KEY_COMBINATION, 'key_combination', '组合键'])]
    #[TestWith([BehaviorType::IDLE_START, 'idle_start', '开始空闲'])]
    #[TestWith([BehaviorType::IDLE_END, 'idle_end', '结束空闲'])]
    #[TestWith([BehaviorType::ACTIVITY_DETECTED, 'activity_detected', '检测到活动'])]
    #[TestWith([BehaviorType::NETWORK_ONLINE, 'network_online', '网络连接'])]
    #[TestWith([BehaviorType::NETWORK_OFFLINE, 'network_offline', '网络断开'])]
    #[TestWith([BehaviorType::CONNECTION_SLOW, 'connection_slow', '网络缓慢'])]
    #[TestWith([BehaviorType::DEVICE_ORIENTATION_CHANGE, 'device_orientation_change', '设备方向改变'])]
    #[TestWith([BehaviorType::SCREEN_RESIZE, 'screen_resize', '屏幕尺寸改变'])]
    #[TestWith([BehaviorType::RAPID_SEEK, 'rapid_seek', '快速拖拽'])]
    #[TestWith([BehaviorType::MULTIPLE_TAB, 'multiple_tab', '多标签页'])]
    #[TestWith([BehaviorType::DEVELOPER_TOOLS, 'developer_tools', '开发者工具'])]
    #[TestWith([BehaviorType::COPY_ATTEMPT, 'copy_attempt', '复制尝试'])]
    public function testValueAndLabel(BehaviorType $enum, string $expectedValue, string $expectedLabel): void
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
     * 测试 from() 方法的异常处理
     */

    /**
     * 测试 value 唯一性
     */
    public function testValueUniqueness(): void
    {
        $cases = BehaviorType::cases();
        $values = array_map(fn (BehaviorType $case) => $case->value, $cases);

        self::assertSame(
            count($values),
            count(array_unique($values)),
            'All enum values must be unique'
        );
    }

    /**
     * 测试 label 唯一性
     */
    public function testLabelUniqueness(): void
    {
        $cases = BehaviorType::cases();
        $labels = array_map(fn (BehaviorType $case) => $case->getLabel(), $cases);

        self::assertSame(
            count($labels),
            count(array_unique($labels)),
            'All enum labels must be unique'
        );
    }

    /**
     * 测试是否为可疑行为
     */
    public function testIsSuspicious(): void
    {
        // 可疑行为
        self::assertTrue(BehaviorType::RAPID_SEEK->isSuspicious());
        self::assertTrue(BehaviorType::MULTIPLE_TAB->isSuspicious());
        self::assertTrue(BehaviorType::DEVELOPER_TOOLS->isSuspicious());
        self::assertTrue(BehaviorType::COPY_ATTEMPT->isSuspicious());

        // 正常行为
        self::assertFalse(BehaviorType::PLAY->isSuspicious());
        self::assertFalse(BehaviorType::PAUSE->isSuspicious());
        self::assertFalse(BehaviorType::MOUSE_CLICK->isSuspicious());
        self::assertFalse(BehaviorType::WINDOW_FOCUS->isSuspicious());
        self::assertFalse(BehaviorType::IDLE_START->isSuspicious());
    }

    /**
     * 测试获取行为分类
     */
    public function testGetCategory(): void
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
    public function testCases(): void
    {
        $cases = BehaviorType::cases();

        self::assertGreaterThan(20, count($cases)); // 至少有20个以上的行为类型

        // 检查一些关键的行为类型是否存在
        self::assertContains(BehaviorType::PLAY, $cases);
        self::assertContains(BehaviorType::PAUSE, $cases);
        self::assertContains(BehaviorType::MOUSE_CLICK, $cases);
        self::assertContains(BehaviorType::WINDOW_FOCUS, $cases);
        self::assertContains(BehaviorType::RAPID_SEEK, $cases);
        self::assertContains(BehaviorType::DEVELOPER_TOOLS, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function testFrom(): void
    {
        self::assertSame(BehaviorType::PLAY, BehaviorType::from('play'));
        self::assertSame(BehaviorType::PAUSE, BehaviorType::from('pause'));
        self::assertSame(BehaviorType::MOUSE_CLICK, BehaviorType::from('mouse_click'));
        self::assertSame(BehaviorType::RAPID_SEEK, BehaviorType::from('rapid_seek'));
    }

    /**
     * 测试 tryFrom
     */
    public function testTryFrom(): void
    {
        self::assertSame(BehaviorType::PLAY, BehaviorType::tryFrom('play'));
        self::assertSame(BehaviorType::DEVELOPER_TOOLS, BehaviorType::tryFrom('developer_tools'));
        self::assertNull(BehaviorType::tryFrom('invalid_behavior'));
    }

    /**
     * 测试分类分组
     */
    public function testCategoryGrouping(): void
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
            $this->assertGreaterThan(0, count($members), "Category '{$category}' should have at least one member");
        }
    }

    /**
     * 测试 toSelectItem 方法
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = BehaviorType::PLAY->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('play', $result['value']);
        $this->assertEquals('播放', $result['label']);
    }
}
