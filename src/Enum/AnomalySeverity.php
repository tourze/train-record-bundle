<?php

namespace Tourze\TrainRecordBundle\Enum;

/**
 * 异常严重程度枚举
 */
enum AnomalySeverity: string
{
    case LOW = 'low';           // 低
    case MEDIUM = 'medium';     // 中
    case HIGH = 'high';         // 高
    case CRITICAL = 'critical'; // 严重

    /**
     * 获取严重程度标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => '低',
            self::MEDIUM => '中',
            self::HIGH => '高',
            self::CRITICAL => '严重',
        };
    }

    /**
     * 获取严重程度描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => '轻微异常，可延后处理',
            self::MEDIUM => '一般异常，需要关注',
            self::HIGH => '重要异常，需要及时处理',
            self::CRITICAL => '严重异常，需要立即处理',
        };
    }

    /**
     * 获取严重程度颜色
     */
    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }

    /**
     * 获取严重程度权重（用于排序）
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    /**
     * 检查是否需要立即处理
     */
    public function requiresImmediateAction(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL]);
    }

    /**
     * 检查是否为高优先级
     */
    public function isHighPriority(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL]);
    }

    /**
     * 获取处理时限（小时）
     */
    public function getProcessingTimeLimit(): int
    {
        return match ($this) {
            self::LOW => 72,      // 3天
            self::MEDIUM => 24,   // 1天
            self::HIGH => 4,      // 4小时
            self::CRITICAL => 1,  // 1小时
        };
    }

    /**
     * 获取所有严重程度
     */
    public static function getAllSeverities(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::CRITICAL,
        ];
    }

    /**
     * 按权重排序
     */
    public static function getSortedByWeight(): array
    {
        $severities = self::getAllSeverities();
        usort($severities, fn($a, $b) => $b->getWeight() <=> $a->getWeight());
        return $severities;
    }

    /**
     * 从字符串创建
     */
    public static function fromString(string $severity): ?self
    {
        return match (strtolower($severity)) {
            'low', '低' => self::LOW,
            'medium', '中' => self::MEDIUM,
            'high', '高' => self::HIGH,
            'critical', '严重' => self::CRITICAL,
            default => null,
        };
    }
} 