<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 学时状态枚举
 * 用于管理有效学时的各种状态
 */
enum StudyTimeStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case VALID = 'valid';                           // 有效学时
    case INVALID = 'invalid';                       // 无效学时
    case PENDING = 'pending';                       // 待确认学时
    case PARTIAL = 'partial';                       // 部分有效学时
    case EXCLUDED = 'excluded';                     // 已排除学时
    case SUSPENDED = 'suspended';                   // 暂停计时
    case REVIEWING = 'reviewing';                   // 审核中
    case APPROVED = 'approved';                     // 已认定
    case REJECTED = 'rejected';                     // 已拒绝
    case EXPIRED = 'expired';                       // 已过期

    public function getLabel(): string
    {
        return match ($this) {
            self::VALID => '有效学时',
            self::INVALID => '无效学时',
            self::PENDING => '待确认学时',
            self::PARTIAL => '部分有效学时',
            self::EXCLUDED => '已排除学时',
            self::SUSPENDED => '暂停计时',
            self::REVIEWING => '审核中',
            self::APPROVED => '已认定',
            self::REJECTED => '已拒绝',
            self::EXPIRED => '已过期',
        };
    }

    /**
     * 获取状态描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::VALID => '符合学时认定标准的有效学习时长',
            self::INVALID => '不符合学时认定标准的无效学习时长',
            self::PENDING => '等待系统或人工确认的学习时长',
            self::PARTIAL => '部分时段有效的学习时长',
            self::EXCLUDED => '因特定原因被排除的学习时长',
            self::SUSPENDED => '因异常情况暂停计时的时长',
            self::REVIEWING => '正在进行人工审核的学习时长',
            self::APPROVED => '已通过认定的最终有效学时',
            self::REJECTED => '审核后被拒绝认定的学时',
            self::EXPIRED => '超过认定期限的过期学时',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getColor(): string
    {
        return match ($this) {
            self::VALID, self::APPROVED => 'green',
            self::INVALID, self::EXCLUDED, self::REJECTED => 'red',
            self::PENDING, self::REVIEWING => 'orange',
            self::PARTIAL => 'yellow',
            self::SUSPENDED => 'blue',
            self::EXPIRED => 'gray',
        };
    }

    /**
     * 获取状态图标
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::VALID, self::APPROVED => 'check-circle',
            self::INVALID, self::EXCLUDED, self::REJECTED => 'x-circle',
            self::PENDING => 'clock',
            self::PARTIAL => 'pie-chart',
            self::SUSPENDED => 'pause',
            self::REVIEWING => 'search',
            self::EXPIRED => 'calendar-x',
        };
    }

    /**
     * 检查是否为最终状态
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::REJECTED,
            self::EXPIRED,
        ]);
    }

    /**
     * 检查是否可以计入有效学时
     */
    public function isCountable(): bool
    {
        return in_array($this, [
            self::VALID,
            self::PARTIAL,
            self::APPROVED,
        ]);
    }

    /**
     * 检查是否需要人工审核
     */
    public function needsReview(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::REVIEWING,
        ]);
    }

    /**
     * 检查是否可以修改
     */
    public function isModifiable(): bool
    {
        return !$this->isFinal();
    }

    /**
     * 检查是否需要提醒学员
     */
    public function requiresNotification(): bool
    {
        return in_array($this, [
            self::INVALID,
            self::EXCLUDED,
            self::SUSPENDED,
            self::REJECTED,
            self::EXPIRED,
        ]);
    }

    /**
     * 获取下一个可能的状态
     */
    public function getNextPossibleStatuses(): array
    {
        return match ($this) {
            self::PENDING => [self::VALID, self::INVALID, self::PARTIAL, self::REVIEWING],
            self::REVIEWING => [self::APPROVED, self::REJECTED, self::PARTIAL],
            self::VALID => [self::APPROVED, self::EXCLUDED, self::REVIEWING],
            self::INVALID => [self::EXCLUDED, self::REVIEWING],
            self::PARTIAL => [self::APPROVED, self::REJECTED, self::REVIEWING],
            self::SUSPENDED => [self::VALID, self::INVALID, self::PENDING],
            self::EXCLUDED => [], // 最终状态
            self::APPROVED => [], // 最终状态
            self::REJECTED => [], // 最终状态
            self::EXPIRED => [],  // 最终状态
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
            self::VALID,
            self::INVALID,
            self::PENDING,
            self::PARTIAL,
            self::EXCLUDED,
            self::SUSPENDED,
            self::REVIEWING,
            self::APPROVED,
            self::REJECTED,
            self::EXPIRED,
        ];
    }

    /**
     * 获取活跃状态（非最终状态）
     */
    public static function getActiveStatuses(): array
    {
        return array_filter(self::getAllStatuses(), fn($status) => !$status->isFinal());
    }

    /**
     * 获取最终状态
     */
    public static function getFinalStatuses(): array
    {
        return array_filter(self::getAllStatuses(), fn($status) => $status->isFinal());
    }

    /**
     * 获取可计入学时的状态
     */
    public static function getCountableStatuses(): array
    {
        return array_filter(self::getAllStatuses(), fn($status) => $status->isCountable());
    }

    /**
     * 获取需要审核的状态
     */
    public static function getReviewStatuses(): array
    {
        return array_filter(self::getAllStatuses(), fn($status) => $status->needsReview());
    }

    /**
     * 从字符串创建状态
     */
    public static function fromString(string $status): ?self
    {
        return match (strtolower($status)) {
            'valid', '有效' => self::VALID,
            'invalid', '无效' => self::INVALID,
            'pending', '待确认' => self::PENDING,
            'partial', '部分' => self::PARTIAL,
            'excluded', '排除' => self::EXCLUDED,
            'suspended', '暂停' => self::SUSPENDED,
            'reviewing', '审核中' => self::REVIEWING,
            'approved', '已认定' => self::APPROVED,
            'rejected', '已拒绝' => self::REJECTED,
            'expired', '过期' => self::EXPIRED,
            default => null,
        };
    }
} 