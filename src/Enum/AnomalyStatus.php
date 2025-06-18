<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 异常状态枚举
 */
enum AnomalyStatus: string
 implements Itemable, Labelable, Selectable{
    
    use ItemTrait;
    use SelectTrait;
case DETECTED = 'detected';         // 已检测
    case INVESTIGATING = 'investigating'; // 调查中
    case RESOLVED = 'resolved';         // 已解决
    case IGNORED = 'ignored';           // 已忽略

    /**
     * 获取状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DETECTED => '已检测',
            self::INVESTIGATING => '调查中',
            self::RESOLVED => '已解决',
            self::IGNORED => '已忽略',
        };
    }

    /**
     * 获取状态描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DETECTED => '异常已被检测到，等待处理',
            self::INVESTIGATING => '异常正在调查处理中',
            self::RESOLVED => '异常已被成功解决',
            self::IGNORED => '异常被标记为忽略',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DETECTED => 'red',
            self::INVESTIGATING => 'orange',
            self::RESOLVED => 'green',
            self::IGNORED => 'gray',
        };
    }

    /**
     * 获取状态图标
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::DETECTED => 'warning',
            self::INVESTIGATING => 'search',
            self::RESOLVED => 'check',
            self::IGNORED => 'minus',
        };
    }

    /**
     * 检查是否为活跃状态
     */
    public function isActive(): bool
    {
        return in_array($this, [self::DETECTED, self::INVESTIGATING]);
    }

    /**
     * 检查是否已完成处理
     */
    public function isCompleted(): bool
    {
        return in_array($this, [self::RESOLVED, self::IGNORED]);
    }

    /**
     * 检查是否需要处理
     */
    public function needsProcessing(): bool
    {
        return $this === self::DETECTED;
    }

    /**
     * 检查是否正在处理
     */
    public function isProcessing(): bool
    {
        return $this === self::INVESTIGATING;
    }

    /**
     * 获取下一个可能的状态
     */
    public function getNextPossibleStatuses(): array
    {
        return match ($this) {
            self::DETECTED => [self::INVESTIGATING, self::RESOLVED, self::IGNORED],
            self::INVESTIGATING => [self::RESOLVED, self::IGNORED],
            self::RESOLVED => [],
            self::IGNORED => [self::INVESTIGATING], // 可以重新调查
        };
    }

    /**
     * 检查是否可以转换到指定状态
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->getNextPossibleStatuses());
    }

    /**
     * 获取所有状态
     */
    public static function getAllStatuses(): array
    {
        return [
            self::DETECTED,
            self::INVESTIGATING,
            self::RESOLVED,
            self::IGNORED,
        ];
    }

    /**
     * 获取活跃状态
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::DETECTED,
            self::INVESTIGATING,
        ];
    }

    /**
     * 获取已完成状态
     */
    public static function getCompletedStatuses(): array
    {
        return [
            self::RESOLVED,
            self::IGNORED,
        ];
    }

    /**
     * 从字符串创建
     */
    public static function fromString(string $status): ?self
    {
        return match (strtolower($status)) {
            'detected', '已检测' => self::DETECTED,
            'investigating', '调查中' => self::INVESTIGATING,
            'resolved', '已解决' => self::RESOLVED,
            'ignored', '已忽略' => self::IGNORED,
            default => null,
        };
    }
} 