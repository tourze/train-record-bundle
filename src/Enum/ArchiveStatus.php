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
 * 档案状态枚举
 */
enum ArchiveStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case ACTIVE = 'active';     // 活跃
    case PROCESSING = 'processing'; // 处理中
    case COMPLETED = 'completed';   // 已完成
    case FAILED = 'failed';         // 失败
    case ARCHIVED = 'archived'; // 已归档
    case EXPIRED = 'expired';   // 已过期

    /**
     * 获取状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '活跃',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::ARCHIVED => '已归档',
            self::EXPIRED => '已过期',
        };
    }

    /**
     * 获取状态描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => '档案处于活跃状态，可以继续记录',
            self::PROCESSING => '档案正在处理中',
            self::COMPLETED => '档案处理已完成',
            self::FAILED => '档案处理失败',
            self::ARCHIVED => '档案已归档，数据已压缩存储',
            self::EXPIRED => '档案已过期，可以清理',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::PROCESSING => 'orange',
            self::COMPLETED => 'blue',
            self::FAILED => 'red',
            self::ARCHIVED => 'gray',
            self::EXPIRED => 'darkred',
        };
    }

    /**
     * 检查是否可以归档
     */
    public function canArchive(): bool
    {
        return self::ACTIVE === $this;
    }

    /**
     * 检查是否已归档
     */
    public function isArchived(): bool
    {
        return self::ARCHIVED === $this;
    }

    /**
     * 检查是否已过期
     */
    public function isExpired(): bool
    {
        return self::EXPIRED === $this;
    }

    /**
     * 获取所有状态
     * @return array<int, self>
     */
    public static function getAllStatuses(): array
    {
        return [
            self::ACTIVE,
            self::ARCHIVED,
            self::EXPIRED,
        ];
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
        return match ($this) {
            self::ACTIVE => 'badge-success',
            self::PROCESSING => 'badge-warning',
            self::COMPLETED => 'badge-info',
            self::FAILED => 'badge-danger',
            self::ARCHIVED => 'badge-secondary',
            self::EXPIRED => 'badge-dark',
        };
    }
}
