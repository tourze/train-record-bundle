<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 学习行为类型枚举
 * 定义学习过程中可能发生的各种行为类型
 */
enum BehaviorType: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    // 视频控制行为
    case PLAY = 'play';
    case PAUSE = 'pause';
    case STOP = 'stop';
    case SEEK = 'seek';
    case FAST_FORWARD = 'fast_forward';
    case VOLUME_CHANGE = 'volume_change';
    case SPEED_CHANGE = 'speed_change';
    case FULLSCREEN_ENTER = 'fullscreen_enter';
    case FULLSCREEN_EXIT = 'fullscreen_exit';

    // 窗口焦点行为
    case WINDOW_FOCUS = 'window_focus';
    case WINDOW_BLUR = 'window_blur';
    case PAGE_VISIBLE = 'page_visible';
    case PAGE_HIDDEN = 'page_hidden';

    // 鼠标行为
    case MOUSE_ENTER = 'mouse_enter';
    case MOUSE_LEAVE = 'mouse_leave';
    case MOUSE_MOVE = 'mouse_move';
    case MOUSE_CLICK = 'mouse_click';

    // 键盘行为
    case KEY_PRESS = 'key_press';
    case KEY_COMBINATION = 'key_combination';

    // 空闲检测行为
    case IDLE_START = 'idle_start';
    case IDLE_END = 'idle_end';
    case ACTIVITY_DETECTED = 'activity_detected';

    // 网络行为
    case NETWORK_ONLINE = 'network_online';
    case NETWORK_OFFLINE = 'network_offline';
    case CONNECTION_SLOW = 'connection_slow';

    // 设备行为
    case DEVICE_ORIENTATION_CHANGE = 'device_orientation_change';
    case SCREEN_RESIZE = 'screen_resize';

    // 异常行为
    case RAPID_SEEK = 'rapid_seek';
    case MULTIPLE_TAB = 'multiple_tab';
    case DEVELOPER_TOOLS = 'developer_tools';
    case COPY_ATTEMPT = 'copy_attempt';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLAY => '播放',
            self::PAUSE => '暂停',
            self::STOP => '停止',
            self::SEEK => '拖拽进度',
            self::FAST_FORWARD => '快进',
            self::VOLUME_CHANGE => '音量调节',
            self::SPEED_CHANGE => '倍速调节',
            self::FULLSCREEN_ENTER => '进入全屏',
            self::FULLSCREEN_EXIT => '退出全屏',
            self::WINDOW_FOCUS => '窗口获得焦点',
            self::WINDOW_BLUR => '窗口失去焦点',
            self::PAGE_VISIBLE => '页面可见',
            self::PAGE_HIDDEN => '页面隐藏',
            self::MOUSE_ENTER => '鼠标进入',
            self::MOUSE_LEAVE => '鼠标离开',
            self::MOUSE_MOVE => '鼠标移动',
            self::MOUSE_CLICK => '鼠标点击',
            self::KEY_PRESS => '按键',
            self::KEY_COMBINATION => '组合键',
            self::IDLE_START => '开始空闲',
            self::IDLE_END => '结束空闲',
            self::ACTIVITY_DETECTED => '检测到活动',
            self::NETWORK_ONLINE => '网络连接',
            self::NETWORK_OFFLINE => '网络断开',
            self::CONNECTION_SLOW => '网络缓慢',
            self::DEVICE_ORIENTATION_CHANGE => '设备方向改变',
            self::SCREEN_RESIZE => '屏幕尺寸改变',
            self::RAPID_SEEK => '快速拖拽',
            self::MULTIPLE_TAB => '多标签页',
            self::DEVELOPER_TOOLS => '开发者工具',
            self::COPY_ATTEMPT => '复制尝试',
        };
    }

    /**
     * 判断是否为可疑行为
     */
    public function isSuspicious(): bool
    {
        return match ($this) {
            self::RAPID_SEEK,
            self::MULTIPLE_TAB,
            self::DEVELOPER_TOOLS,
            self::COPY_ATTEMPT => true,
            default => false,
        };
    }

    /**
     * 获取行为分类
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::PLAY,
            self::PAUSE,
            self::STOP,
            self::SEEK,
            self::FAST_FORWARD,
            self::VOLUME_CHANGE,
            self::SPEED_CHANGE,
            self::FULLSCREEN_ENTER,
            self::FULLSCREEN_EXIT => 'video_control',

            self::WINDOW_FOCUS,
            self::WINDOW_BLUR,
            self::PAGE_VISIBLE,
            self::PAGE_HIDDEN => 'window_focus',

            self::MOUSE_ENTER,
            self::MOUSE_LEAVE,
            self::MOUSE_MOVE,
            self::MOUSE_CLICK => 'mouse_activity',

            self::KEY_PRESS,
            self::KEY_COMBINATION => 'keyboard_activity',

            self::IDLE_START,
            self::IDLE_END,
            self::ACTIVITY_DETECTED => 'idle_detection',

            self::NETWORK_ONLINE,
            self::NETWORK_OFFLINE,
            self::CONNECTION_SLOW => 'network_status',

            self::DEVICE_ORIENTATION_CHANGE,
            self::SCREEN_RESIZE => 'device_status',

            self::RAPID_SEEK,
            self::MULTIPLE_TAB,
            self::DEVELOPER_TOOLS,
            self::COPY_ATTEMPT => 'suspicious_behavior',
        };
    }

    /**
     * 获取Badge样式
     */
    public function getBadge(): string
    {
        return $this->getLabel();
    }

    /**
     * 获取Badge样式类
     */
    public function getBadgeClass(): string
    {
        return match ($this->getCategory()) {
            'video_control' => 'badge-primary',
            'window_focus' => 'badge-info',
            'mouse_activity' => 'badge-secondary',
            'keyboard_activity' => 'badge-secondary',
            'idle_detection' => 'badge-warning',
            'network_status' => 'badge-info',
            'device_status' => 'badge-secondary',
            'suspicious_behavior' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
