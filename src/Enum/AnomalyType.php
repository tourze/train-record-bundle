<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 学习异常类型枚举
 */
enum AnomalyType: string
 implements Itemable, Labelable, Selectable{
    
    use ItemTrait;
    use SelectTrait;
case MULTIPLE_DEVICE = 'multiple_device';           // 多设备登录
    case RAPID_PROGRESS = 'rapid_progress';             // 快速进度异常
    case WINDOW_SWITCH = 'window_switch';               // 窗口切换异常
    case IDLE_TIMEOUT = 'idle_timeout';                 // 空闲超时
    case FACE_DETECT_FAIL = 'face_detect_fail';         // 人脸检测失败
    case NETWORK_ANOMALY = 'network_anomaly';           // 网络异常
    case SUSPICIOUS_BEHAVIOR = 'suspicious_behavior';    // 可疑行为
    case DEVICE_CHANGE = 'device_change';               // 设备切换
    case IP_CHANGE = 'ip_change';                       // IP地址变更
    case TIME_ANOMALY = 'time_anomaly';                 // 时间异常
    case PROGRESS_ROLLBACK = 'progress_rollback';       // 进度回退
    case CONCURRENT_SESSION = 'concurrent_session';     // 并发会话
    case INVALID_OPERATION = 'invalid_operation';       // 无效操作
    case SECURITY_VIOLATION = 'security_violation';     // 安全违规
    case DATA_INCONSISTENCY = 'data_inconsistency';     // 数据不一致

    /**
     * 获取异常类型标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MULTIPLE_DEVICE => '多设备登录',
            self::RAPID_PROGRESS => '快速进度异常',
            self::WINDOW_SWITCH => '窗口切换异常',
            self::IDLE_TIMEOUT => '空闲超时',
            self::FACE_DETECT_FAIL => '人脸检测失败',
            self::NETWORK_ANOMALY => '网络异常',
            self::SUSPICIOUS_BEHAVIOR => '可疑行为',
            self::DEVICE_CHANGE => '设备切换',
            self::IP_CHANGE => 'IP地址变更',
            self::TIME_ANOMALY => '时间异常',
            self::PROGRESS_ROLLBACK => '进度回退',
            self::CONCURRENT_SESSION => '并发会话',
            self::INVALID_OPERATION => '无效操作',
            self::SECURITY_VIOLATION => '安全违规',
            self::DATA_INCONSISTENCY => '数据不一致',
        };
    }

    /**
     * 获取异常类型描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MULTIPLE_DEVICE => '检测到用户在多个设备上同时学习',
            self::RAPID_PROGRESS => '学习进度推进过快，可能存在作弊行为',
            self::WINDOW_SWITCH => '频繁切换窗口或失去焦点',
            self::IDLE_TIMEOUT => '长时间无操作，可能已离开学习',
            self::FACE_DETECT_FAIL => '人脸识别验证失败',
            self::NETWORK_ANOMALY => '网络连接异常或中断',
            self::SUSPICIOUS_BEHAVIOR => '检测到可疑的学习行为模式',
            self::DEVICE_CHANGE => '学习过程中更换了设备',
            self::IP_CHANGE => '学习过程中IP地址发生变更',
            self::TIME_ANOMALY => '学习时间记录异常',
            self::PROGRESS_ROLLBACK => '学习进度出现回退',
            self::CONCURRENT_SESSION => '检测到并发学习会话',
            self::INVALID_OPERATION => '执行了无效的学习操作',
            self::SECURITY_VIOLATION => '违反了安全策略',
            self::DATA_INCONSISTENCY => '学习数据存在不一致',
        };
    }

    /**
     * 获取异常类型分类
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::MULTIPLE_DEVICE, self::DEVICE_CHANGE, self::CONCURRENT_SESSION => 'device',
            self::RAPID_PROGRESS, self::PROGRESS_ROLLBACK, self::TIME_ANOMALY => 'progress',
            self::WINDOW_SWITCH, self::IDLE_TIMEOUT, self::SUSPICIOUS_BEHAVIOR => 'behavior',
            self::FACE_DETECT_FAIL, self::SECURITY_VIOLATION => 'security',
            self::NETWORK_ANOMALY, self::IP_CHANGE => 'network',
            self::INVALID_OPERATION, self::DATA_INCONSISTENCY => 'system',
        };
    }

    /**
     * 获取默认严重程度
     */
    public function getDefaultSeverity(): AnomalySeverity
    {
        return match ($this) {
            self::SECURITY_VIOLATION, self::MULTIPLE_DEVICE => AnomalySeverity::CRITICAL,
            self::FACE_DETECT_FAIL, self::RAPID_PROGRESS, self::CONCURRENT_SESSION => AnomalySeverity::HIGH,
            self::WINDOW_SWITCH, self::DEVICE_CHANGE, self::SUSPICIOUS_BEHAVIOR => AnomalySeverity::MEDIUM,
            self::IDLE_TIMEOUT, self::NETWORK_ANOMALY, self::IP_CHANGE => AnomalySeverity::LOW,
            default => AnomalySeverity::MEDIUM,
        };
    }

    /**
     * 检查是否需要立即处理
     */
    public function requiresImmediateAction(): bool
    {
        return in_array($this, [
            self::SECURITY_VIOLATION,
            self::MULTIPLE_DEVICE,
            self::FACE_DETECT_FAIL,
            self::CONCURRENT_SESSION,
        ]);
    }

    /**
     * 获取所有异常类型
     */
    public static function getAllTypes(): array
    {
        return [
            self::MULTIPLE_DEVICE,
            self::RAPID_PROGRESS,
            self::WINDOW_SWITCH,
            self::IDLE_TIMEOUT,
            self::FACE_DETECT_FAIL,
            self::NETWORK_ANOMALY,
            self::SUSPICIOUS_BEHAVIOR,
            self::DEVICE_CHANGE,
            self::IP_CHANGE,
            self::TIME_ANOMALY,
            self::PROGRESS_ROLLBACK,
            self::CONCURRENT_SESSION,
            self::INVALID_OPERATION,
            self::SECURITY_VIOLATION,
            self::DATA_INCONSISTENCY,
        ];
    }

    /**
     * 按分类获取异常类型
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::getAllTypes(), fn($type) => $type->getCategory() === $category);
    }
} 