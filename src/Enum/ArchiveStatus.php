<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 档案状态枚举
 */
enum ArchiveStatus: string
 implements Itemable, Labelable, Selectable{
    
    use ItemTrait;
    use SelectTrait;
case ACTIVE = 'active';     // 活跃
    case ARCHIVED = 'archived'; // 已归档
    case EXPIRED = 'expired';   // 已过期

    /**
     * 获取状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '活跃',
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
            self::ARCHIVED => 'blue',
            self::EXPIRED => 'red',
        };
    }

    /**
     * 检查是否可以归档
     */
    public function canArchive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * 检查是否已归档
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * 检查是否已过期
     */
    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    /**
     * 获取所有状态
     */
    public static function getAllStatuses(): array
    {
        return [
            self::ACTIVE,
            self::ARCHIVED,
            self::EXPIRED,
        ];
    }
} 