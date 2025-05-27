<?php

namespace Tourze\TrainRecordBundle\Enum;

/**
 * 统计类型枚举
 */
enum StatisticsType: string
{
    case USER = 'user';                     // 用户统计
    case COURSE = 'course';                 // 课程统计
    case BEHAVIOR = 'behavior';             // 行为统计
    case ANOMALY = 'anomaly';               // 异常统计
    case DEVICE = 'device';                 // 设备统计
    case PROGRESS = 'progress';             // 进度统计
    case DURATION = 'duration';             // 时长统计
    case EFFICIENCY = 'efficiency';         // 效率统计
    case COMPLETION = 'completion';         // 完成率统计
    case ENGAGEMENT = 'engagement';         // 参与度统计
    case QUALITY = 'quality';               // 质量统计
    case TREND = 'trend';                   // 趋势统计

    /**
     * 获取统计类型标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::USER => '用户统计',
            self::COURSE => '课程统计',
            self::BEHAVIOR => '行为统计',
            self::ANOMALY => '异常统计',
            self::DEVICE => '设备统计',
            self::PROGRESS => '进度统计',
            self::DURATION => '时长统计',
            self::EFFICIENCY => '效率统计',
            self::COMPLETION => '完成率统计',
            self::ENGAGEMENT => '参与度统计',
            self::QUALITY => '质量统计',
            self::TREND => '趋势统计',
        };
    }

    /**
     * 获取统计类型描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER => '统计用户活跃度、注册数、学习人数等用户相关指标',
            self::COURSE => '统计课程热度、完成率、评分等课程相关指标',
            self::BEHAVIOR => '统计学习行为模式、交互频率等行为相关指标',
            self::ANOMALY => '统计异常检测、处理效率等异常相关指标',
            self::DEVICE => '统计设备类型、浏览器分布等设备相关指标',
            self::PROGRESS => '统计学习进度、完成情况等进度相关指标',
            self::DURATION => '统计学习时长、有效时长等时长相关指标',
            self::EFFICIENCY => '统计学习效率、专注度等效率相关指标',
            self::COMPLETION => '统计完成率、通过率等完成相关指标',
            self::ENGAGEMENT => '统计参与度、活跃度等参与相关指标',
            self::QUALITY => '统计学习质量、评分等质量相关指标',
            self::TREND => '统计发展趋势、变化趋势等趋势相关指标',
        };
    }

    /**
     * 获取统计类型分类
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::USER, self::DEVICE => 'user_related',
            self::COURSE, self::PROGRESS, self::COMPLETION => 'course_related',
            self::BEHAVIOR, self::ENGAGEMENT, self::QUALITY => 'behavior_related',
            self::ANOMALY => 'security_related',
            self::DURATION, self::EFFICIENCY => 'performance_related',
            self::TREND => 'analysis_related',
        };
    }

    /**
     * 获取统计优先级
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::USER, self::COURSE, self::PROGRESS => 1,      // 高优先级
            self::BEHAVIOR, self::DURATION, self::COMPLETION => 2,  // 中优先级
            self::ANOMALY, self::DEVICE, self::EFFICIENCY => 3,     // 低优先级
            self::ENGAGEMENT, self::QUALITY, self::TREND => 4,      // 扩展优先级
        };
    }

    /**
     * 检查是否为核心统计
     */
    public function isCoreStatistics(): bool
    {
        return in_array($this, [
            self::USER,
            self::COURSE,
            self::PROGRESS,
            self::DURATION,
            self::COMPLETION,
        ]);
    }

    /**
     * 检查是否需要实时更新
     */
    public function needsRealTimeUpdate(): bool
    {
        return in_array($this, [
            self::USER,
            self::BEHAVIOR,
            self::ANOMALY,
            self::PROGRESS,
        ]);
    }

    /**
     * 获取所有统计类型
     */
    public static function getAllTypes(): array
    {
        return [
            self::USER,
            self::COURSE,
            self::BEHAVIOR,
            self::ANOMALY,
            self::DEVICE,
            self::PROGRESS,
            self::DURATION,
            self::EFFICIENCY,
            self::COMPLETION,
            self::ENGAGEMENT,
            self::QUALITY,
            self::TREND,
        ];
    }

    /**
     * 按分类获取统计类型
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::getAllTypes(), fn($type) => $type->getCategory() === $category);
    }

    /**
     * 获取核心统计类型
     */
    public static function getCoreTypes(): array
    {
        return array_filter(self::getAllTypes(), fn($type) => $type->isCoreStatistics());
    }

    /**
     * 按优先级排序
     */
    public static function getSortedByPriority(): array
    {
        $types = self::getAllTypes();
        usort($types, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
        return $types;
    }
} 