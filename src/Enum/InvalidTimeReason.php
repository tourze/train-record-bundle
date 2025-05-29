<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 无效时长原因枚举
 * 定义不计入有效学习时长的各种情形
 */
enum InvalidTimeReason: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    // a) 学员登录后浏览网页信息，以及在线测试的时长
    case BROWSING_WEB_INFO = 'browsing_web_info';           // 浏览网页信息
    case ONLINE_TESTING = 'online_testing';                 // 在线测试时长

    // b) 学员登录或学习过程中身份验证失败，验证失败提示后的时长
    case IDENTITY_VERIFICATION_FAILED = 'identity_verification_failed';  // 身份验证失败

    // c) 学员学习过程中，与网络平台交互时间间隔超过预设值
    case INTERACTION_TIMEOUT = 'interaction_timeout';       // 交互超时
    case IDLE_TIMEOUT = 'idle_timeout';                     // 空闲超时
    case NO_ACTIVITY_DETECTED = 'no_activity_detected';     // 无活动检测

    // d) 学员日累计有效学习时长超过预设值的，超出部分的时长
    case DAILY_LIMIT_EXCEEDED = 'daily_limit_exceeded';     // 日累计时长超限

    // e) 学员未完成相应课程后的在线测试
    case INCOMPLETE_COURSE_TEST = 'incomplete_course_test';  // 未完成课程测试

    // 其他技术原因
    case WINDOW_FOCUS_LOST = 'window_focus_lost';           // 窗口失去焦点
    case PAGE_HIDDEN = 'page_hidden';                       // 页面隐藏
    case MULTIPLE_DEVICE_LOGIN = 'multiple_device_login';   // 多设备登录
    case NETWORK_DISCONNECTED = 'network_disconnected';     // 网络断开
    case SYSTEM_ERROR = 'system_error';                     // 系统错误
    case SUSPICIOUS_BEHAVIOR = 'suspicious_behavior';       // 可疑行为
    case MANUAL_EXCLUSION = 'manual_exclusion';             // 手动排除

    public function getLabel(): string
    {
        return match ($this) {
            self::BROWSING_WEB_INFO => '浏览网页信息',
            self::ONLINE_TESTING => '在线测试时长',
            self::IDENTITY_VERIFICATION_FAILED => '身份验证失败',
            self::INTERACTION_TIMEOUT => '交互超时',
            self::IDLE_TIMEOUT => '空闲超时',
            self::NO_ACTIVITY_DETECTED => '无活动检测',
            self::DAILY_LIMIT_EXCEEDED => '日累计时长超限',
            self::INCOMPLETE_COURSE_TEST => '未完成课程测试',
            self::WINDOW_FOCUS_LOST => '窗口失去焦点',
            self::PAGE_HIDDEN => '页面隐藏',
            self::MULTIPLE_DEVICE_LOGIN => '多设备登录',
            self::NETWORK_DISCONNECTED => '网络断开',
            self::SYSTEM_ERROR => '系统错误',
            self::SUSPICIOUS_BEHAVIOR => '可疑行为',
            self::MANUAL_EXCLUSION => '手动排除',
        };
    }

    /**
     * 获取详细描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::BROWSING_WEB_INFO => '学员登录后浏览网页信息的时长不计入有效学习时长',
            self::ONLINE_TESTING => '在线测试过程中的时长不计入有效学习时长',
            self::IDENTITY_VERIFICATION_FAILED => '身份验证失败提示后的时长不计入有效学习时长',
            self::INTERACTION_TIMEOUT => '与网络平台交互间隔超过预设值的时长不计入',
            self::IDLE_TIMEOUT => '学员长时间无操作的空闲时长不计入',
            self::NO_ACTIVITY_DETECTED => '检测到无学习活动的时长不计入',
            self::DAILY_LIMIT_EXCEEDED => '日累计有效学习时长超过预设值的超出部分不计入',
            self::INCOMPLETE_COURSE_TEST => '未完成相应课程在线测试时，整个课程学时不予认定',
            self::WINDOW_FOCUS_LOST => '学习窗口失去焦点期间的时长不计入',
            self::PAGE_HIDDEN => '学习页面被隐藏期间的时长不计入',
            self::MULTIPLE_DEVICE_LOGIN => '多设备同时登录期间的重复时长不计入',
            self::NETWORK_DISCONNECTED => '网络断开期间的时长不计入',
            self::SYSTEM_ERROR => '系统错误期间的时长不计入',
            self::SUSPICIOUS_BEHAVIOR => '检测到可疑学习行为的时长不计入',
            self::MANUAL_EXCLUSION => '管理员手动排除的时长不计入',
        };
    }

    /**
     * 获取规范依据分类
     */
    public function getRegulationCategory(): string
    {
        return match ($this) {
            self::BROWSING_WEB_INFO, self::ONLINE_TESTING => 'regulation_a',
            self::IDENTITY_VERIFICATION_FAILED => 'regulation_b',
            self::INTERACTION_TIMEOUT, self::IDLE_TIMEOUT, self::NO_ACTIVITY_DETECTED => 'regulation_c',
            self::DAILY_LIMIT_EXCEEDED => 'regulation_d',
            self::INCOMPLETE_COURSE_TEST => 'regulation_e',
            default => 'technical_reason',
        };
    }

    /**
     * 获取严重程度
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::INCOMPLETE_COURSE_TEST, self::IDENTITY_VERIFICATION_FAILED => 'critical',
            self::DAILY_LIMIT_EXCEEDED, self::MULTIPLE_DEVICE_LOGIN, self::SUSPICIOUS_BEHAVIOR => 'high',
            self::INTERACTION_TIMEOUT, self::IDLE_TIMEOUT, self::WINDOW_FOCUS_LOST => 'medium',
            default => 'low',
        };
    }

    /**
     * 检查是否影响整个课程认定
     */
    public function affectsWholeCourse(): bool
    {
        return match ($this) {
            self::INCOMPLETE_COURSE_TEST, self::IDENTITY_VERIFICATION_FAILED => true,
            default => false,
        };
    }

    /**
     * 检查是否需要学员提示
     */
    public function requiresStudentNotification(): bool
    {
        return match ($this) {
            self::BROWSING_WEB_INFO,
            self::ONLINE_TESTING,
            self::IDENTITY_VERIFICATION_FAILED,
            self::INTERACTION_TIMEOUT,
            self::IDLE_TIMEOUT,
            self::DAILY_LIMIT_EXCEEDED,
            self::INCOMPLETE_COURSE_TEST => true,
            default => false,
        };
    }

    /**
     * 获取提示消息
     */
    public function getNotificationMessage(): string
    {
        return match ($this) {
            self::BROWSING_WEB_INFO => '当前正在浏览网页信息，此时长不计入有效学习时长',
            self::ONLINE_TESTING => '当前正在进行在线测试，此时长不计入有效学习时长',
            self::IDENTITY_VERIFICATION_FAILED => '身份验证失败，请重新验证。验证失败期间的时长不计入有效学习时长',
            self::INTERACTION_TIMEOUT => '检测到您长时间未与平台交互，此期间时长不计入有效学习时长',
            self::IDLE_TIMEOUT => '检测到您长时间无操作，请点击继续学习',
            self::DAILY_LIMIT_EXCEEDED => '您今日的有效学习时长已达到上限，超出部分不计入认定',
            self::INCOMPLETE_COURSE_TEST => '请完成课程在线测试，否则整个课程学时不予认定',
            default => '此时长不计入有效学习时长',
        };
    }

    /**
     * 获取所有规范要求的原因
     */
    public static function getRegulationReasons(): array
    {
        return [
            self::BROWSING_WEB_INFO,
            self::ONLINE_TESTING,
            self::IDENTITY_VERIFICATION_FAILED,
            self::INTERACTION_TIMEOUT,
            self::IDLE_TIMEOUT,
            self::NO_ACTIVITY_DETECTED,
            self::DAILY_LIMIT_EXCEEDED,
            self::INCOMPLETE_COURSE_TEST,
        ];
    }

    /**
     * 获取技术原因
     */
    public static function getTechnicalReasons(): array
    {
        return [
            self::WINDOW_FOCUS_LOST,
            self::PAGE_HIDDEN,
            self::MULTIPLE_DEVICE_LOGIN,
            self::NETWORK_DISCONNECTED,
            self::SYSTEM_ERROR,
            self::SUSPICIOUS_BEHAVIOR,
            self::MANUAL_EXCLUSION,
        ];
    }

    /**
     * 按严重程度分组
     */
    public static function getBySeverity(string $severity): array
    {
        return array_filter(self::cases(), fn($case) => $case->getSeverity() === $severity);
    }
}
