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
 * 统计周期枚举
 */
enum StatisticsPeriod: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DAILY = 'daily';           // 日统计
    case WEEKLY = 'weekly';         // 周统计
    case MONTHLY = 'monthly';       // 月统计
    case QUARTERLY = 'quarterly';   // 季度统计
    case YEARLY = 'yearly';         // 年统计
    case HOURLY = 'hourly';         // 小时统计
    case REAL_TIME = 'real_time';   // 实时统计

    /**
     * 获取周期标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HOURLY => '小时',
            self::DAILY => '日',
            self::WEEKLY => '周',
            self::MONTHLY => '月',
            self::QUARTERLY => '季度',
            self::YEARLY => '年',
            self::REAL_TIME => '实时',
        };
    }

    /**
     * 获取徽章颜色
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::HOURLY, self::REAL_TIME => 'info',
            self::DAILY => 'primary',
            self::WEEKLY => 'success',
            self::MONTHLY => 'warning',
            self::QUARTERLY => 'secondary',
            self::YEARLY => 'dark',
        };
    }

    /**
     * 获取徽章样式类
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::HOURLY, self::REAL_TIME => 'bg-info',
            self::DAILY => 'bg-primary',
            self::WEEKLY => 'bg-success',
            self::MONTHLY => 'bg-warning',
            self::QUARTERLY => 'bg-secondary',
            self::YEARLY => 'bg-dark',
        };
    }

    /**
     * 获取徽章标识
     */
    public function getBadge(): string
    {
        return $this->getBadgeClass();
    }

    /**
     * 获取周期描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HOURLY => '按小时统计数据',
            self::DAILY => '按天统计数据',
            self::WEEKLY => '按周统计数据',
            self::MONTHLY => '按月统计数据',
            self::QUARTERLY => '按季度统计数据',
            self::YEARLY => '按年统计数据',
            self::REAL_TIME => '实时统计数据',
        };
    }

    /**
     * 获取周期的秒数
     */
    public function getSeconds(): int
    {
        return match ($this) {
            self::HOURLY => 3600,           // 1小时
            self::DAILY => 86400,           // 1天
            self::WEEKLY => 604800,         // 7天
            self::MONTHLY => 2592000,       // 30天
            self::QUARTERLY => 7776000,     // 90天
            self::YEARLY => 31536000,       // 365天
            self::REAL_TIME => 0,           // 实时
        };
    }

    /**
     * 获取DateInterval格式
     */
    public function getDateInterval(): string
    {
        return match ($this) {
            self::HOURLY => 'PT1H',
            self::DAILY => 'P1D',
            self::WEEKLY => 'P1W',
            self::MONTHLY => 'P1M',
            self::QUARTERLY => 'P3M',
            self::YEARLY => 'P1Y',
            self::REAL_TIME => 'PT0S',
        };
    }

    /**
     * 获取MySQL日期格式
     */
    public function getMySQLDateFormat(): string
    {
        return match ($this) {
            self::HOURLY => '%Y-%m-%d %H:00:00',
            self::DAILY => '%Y-%m-%d',
            self::WEEKLY => '%Y-%u',
            self::MONTHLY => '%Y-%m',
            self::QUARTERLY => '%Y-Q%q',
            self::YEARLY => '%Y',
            self::REAL_TIME => '%Y-%m-%d %H:%i:%s',
        };
    }

    /**
     * 获取PHP日期格式
     */
    public function getPHPDateFormat(): string
    {
        return match ($this) {
            self::HOURLY => 'Y-m-d H:00:00',
            self::DAILY => 'Y-m-d',
            self::WEEKLY => 'Y-W',
            self::MONTHLY => 'Y-m',
            self::QUARTERLY => 'Y-\QQ',
            self::YEARLY => 'Y',
            self::REAL_TIME => 'Y-m-d H:i:s',
        };
    }

    /**
     * 获取统计频率（每天执行次数）
     */
    public function getFrequencyPerDay(): int
    {
        return match ($this) {
            self::HOURLY => 24,         // 每小时1次
            self::DAILY => 1,           // 每天1次
            self::WEEKLY => 1,          // 每周1次（按天算约0.14次）
            self::MONTHLY => 1,         // 每月1次（按天算约0.03次）
            self::QUARTERLY => 1,       // 每季度1次
            self::YEARLY => 1,          // 每年1次
            self::REAL_TIME => 1440,    // 每分钟1次
        };
    }

    /**
     * 检查是否为高频统计
     */
    public function isHighFrequency(): bool
    {
        return in_array($this, [self::REAL_TIME, self::HOURLY], true);
    }

    /**
     * 检查是否为低频统计
     */
    public function isLowFrequency(): bool
    {
        return in_array($this, [self::QUARTERLY, self::YEARLY], true);
    }

    /**
     * 获取下一个统计时间
     */
    public function getNextStatisticsTime(\DateTimeInterface $currentTime): \DateTime
    {
        $nextTime = \DateTime::createFromInterface($currentTime);

        switch ($this) {
            case self::HOURLY:
                $nextTime->add(new \DateInterval('PT1H'));
                break;
            case self::DAILY:
                $nextTime->add(new \DateInterval('P1D'));
                break;
            case self::WEEKLY:
                $nextTime->add(new \DateInterval('P1W'));
                break;
            case self::MONTHLY:
                $nextTime->add(new \DateInterval('P1M'));
                break;
            case self::QUARTERLY:
                $nextTime->add(new \DateInterval('P3M'));
                break;
            case self::YEARLY:
                $nextTime->add(new \DateInterval('P1Y'));
                break;
            case self::REAL_TIME:
                $nextTime->add(new \DateInterval('PT1M'));
                break;
        }

        return $nextTime;
    }

    /**
     * 格式化统计时间
     */
    public function formatStatisticsTime(\DateTimeInterface $time): string
    {
        return $time->format($this->getPHPDateFormat());
    }

    /**
     * 获取所有周期
     * @return array<int, self>
     */
    public static function getAllPeriods(): array
    {
        return [
            self::REAL_TIME,
            self::HOURLY,
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::QUARTERLY,
            self::YEARLY,
        ];
    }

    /**
     * 获取常用周期
     * @return array<int, self>
     */
    public static function getCommonPeriods(): array
    {
        return [
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::YEARLY,
        ];
    }

    /**
     * 按频率排序
     * @return array<int, self>
     */
    public static function getSortedByFrequency(): array
    {
        $periods = self::getAllPeriods();
        usort($periods, fn ($a, $b) => $b->getFrequencyPerDay() <=> $a->getFrequencyPerDay());

        return $periods;
    }

    /**
     * 从字符串创建
     */
    public static function fromString(string $period): ?self
    {
        return match (strtolower($period)) {
            'hourly', '小时' => self::HOURLY,
            'daily', '日', '天' => self::DAILY,
            'weekly', '周' => self::WEEKLY,
            'monthly', '月' => self::MONTHLY,
            'quarterly', '季度' => self::QUARTERLY,
            'yearly', '年' => self::YEARLY,
            'real_time', '实时' => self::REAL_TIME,
            default => null,
        };
    }
}
