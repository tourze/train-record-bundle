<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Repository\LearnStatisticsRepository;

/**
 * 学习统计实体
 *
 * 存储各种维度的学习统计数据，支持按日、周、月等不同周期统计。
 * 包括用户统计、课程统计、行为统计、异常统计、设备统计等。
 *
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnStatisticsRepository::class)]
#[ORM\Table(name: 'job_training_learn_statistics', options: ['comment' => '学习统计'])]
#[ORM\UniqueConstraint(name: 'uniq_type_period_date', columns: ['statistics_type', 'statistics_period', 'statistics_date'])]
#[ORM\Index(name: 'job_training_learn_statistics_idx_type_period', columns: ['statistics_type', 'statistics_period'])]
class LearnStatistics implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[ORM\Column(length: 30, enumType: StatisticsType::class, options: ['comment' => '统计类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [StatisticsType::class, 'cases'])]
    private StatisticsType $statisticsType;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: StatisticsPeriod::class, options: ['comment' => '统计周期'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [StatisticsPeriod::class, 'cases'])]
    private StatisticsPeriod $statisticsPeriod;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '统计日期'])]
    #[Assert\NotNull]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $statisticsDate;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '用户统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $userStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '课程统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $courseStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $behaviorStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $anomalyStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '设备统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $deviceStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '进度统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $progressStatistics = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '时长统计JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $durationStatistics = null;

    #[ORM\Column(options: ['comment' => '总用户数', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $totalUsers = 0;

    #[ORM\Column(options: ['comment' => '活跃用户数', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $activeUsers = 0;

    #[ORM\Column(options: ['comment' => '总会话数', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $totalSessions = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, options: ['comment' => '总学习时长（秒）', 'default' => '0.0000'])]
    #[Assert\Length(max: 17)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Total duration must be a valid decimal')]
    private string $totalDuration = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, options: ['comment' => '有效学习时长（秒）', 'default' => '0.0000'])]
    #[Assert\Length(max: 17)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Effective duration must be a valid decimal')]
    private string $effectiveDuration = '0.0000';

    #[ORM\Column(options: ['comment' => '异常数量', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $anomalyCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '完成率（%）'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Completion rate must be a valid decimal')]
    private ?string $completionRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4, nullable: true, options: ['comment' => '平均学习效率'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Average efficiency must be a valid decimal')]
    private ?string $averageEfficiency = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $extendedData = null;

    public function __construct()
    {
        $this->statisticsDate = new \DateTimeImmutable();
    }

    public function getStatisticsType(): StatisticsType
    {
        return $this->statisticsType;
    }

    public function setStatisticsType(StatisticsType $statisticsType): void
    {
        $this->statisticsType = $statisticsType;
    }

    public function getStatisticsPeriod(): StatisticsPeriod
    {
        return $this->statisticsPeriod;
    }

    public function setStatisticsPeriod(StatisticsPeriod $statisticsPeriod): void
    {
        $this->statisticsPeriod = $statisticsPeriod;
    }

    public function getStatisticsDate(): \DateTimeImmutable
    {
        return $this->statisticsDate;
    }

    public function setStatisticsDate(\DateTimeImmutable $statisticsDate): void
    {
        $this->statisticsDate = $statisticsDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUserStatistics(): ?array
    {
        return $this->userStatistics;
    }

    /**
     * @param array<string, mixed>|null $userStatistics
     */
    public function setUserStatistics(?array $userStatistics): void
    {
        $this->userStatistics = $userStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCourseStatistics(): ?array
    {
        return $this->courseStatistics;
    }

    /**
     * @param array<string, mixed>|null $courseStatistics
     */
    public function setCourseStatistics(?array $courseStatistics): void
    {
        $this->courseStatistics = $courseStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBehaviorStatistics(): ?array
    {
        return $this->behaviorStatistics;
    }

    /**
     * @param array<string, mixed>|null $behaviorStatistics
     */
    public function setBehaviorStatistics(?array $behaviorStatistics): void
    {
        $this->behaviorStatistics = $behaviorStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAnomalyStatistics(): ?array
    {
        return $this->anomalyStatistics;
    }

    /**
     * @param array<string, mixed>|null $anomalyStatistics
     */
    public function setAnomalyStatistics(?array $anomalyStatistics): void
    {
        $this->anomalyStatistics = $anomalyStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDeviceStatistics(): ?array
    {
        return $this->deviceStatistics;
    }

    /**
     * @param array<string, mixed>|null $deviceStatistics
     */
    public function setDeviceStatistics(?array $deviceStatistics): void
    {
        $this->deviceStatistics = $deviceStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProgressStatistics(): ?array
    {
        return $this->progressStatistics;
    }

    /**
     * @param array<string, mixed>|null $progressStatistics
     */
    public function setProgressStatistics(?array $progressStatistics): void
    {
        $this->progressStatistics = $progressStatistics;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDurationStatistics(): ?array
    {
        return $this->durationStatistics;
    }

    /**
     * @param array<string, mixed>|null $durationStatistics
     */
    public function setDurationStatistics(?array $durationStatistics): void
    {
        $this->durationStatistics = $durationStatistics;
    }

    public function getTotalUsers(): int
    {
        return $this->totalUsers;
    }

    public function setTotalUsers(int $totalUsers): void
    {
        $this->totalUsers = max(0, $totalUsers);
    }

    public function getActiveUsers(): int
    {
        return $this->activeUsers;
    }

    public function setActiveUsers(int $activeUsers): void
    {
        $this->activeUsers = max(0, $activeUsers);
    }

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function setTotalSessions(int $totalSessions): void
    {
        $this->totalSessions = max(0, $totalSessions);
    }

    public function getTotalDuration(): float
    {
        return (float) $this->totalDuration;
    }

    public function setTotalDuration(float $totalDuration): void
    {
        $this->totalDuration = (string) max(0, $totalDuration);
    }

    public function getEffectiveDuration(): float
    {
        return (float) $this->effectiveDuration;
    }

    public function setEffectiveDuration(float $effectiveDuration): void
    {
        $this->effectiveDuration = (string) max(0, $effectiveDuration);
    }

    public function getAnomalyCount(): int
    {
        return $this->anomalyCount;
    }

    public function setAnomalyCount(int $anomalyCount): void
    {
        $this->anomalyCount = max(0, $anomalyCount);
    }

    public function getCompletionRate(): ?float
    {
        return null !== $this->completionRate ? (float) $this->completionRate : null;
    }

    public function setCompletionRate(?float $completionRate): void
    {
        $this->completionRate = null !== $completionRate ? (string) max(0, min(100, $completionRate)) : null;
    }

    public function getAverageEfficiency(): ?float
    {
        return null !== $this->averageEfficiency ? (float) $this->averageEfficiency : null;
    }

    public function setAverageEfficiency(?float $averageEfficiency): void
    {
        $this->averageEfficiency = null !== $averageEfficiency ? (string) max(0, min(1, $averageEfficiency)) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExtendedData(): ?array
    {
        return $this->extendedData;
    }

    /**
     * @param array<string, mixed>|null $extendedData
     */
    public function setExtendedData(?array $extendedData): void
    {
        $this->extendedData = $extendedData;
    }

    /**
     * 计算用户活跃率
     */
    public function getUserActiveRate(): float
    {
        if ($this->totalUsers <= 0) {
            return 0;
        }

        return $this->activeUsers / $this->totalUsers;
    }

    /**
     * 计算学习效率
     */
    public function getLearningEfficiency(): float
    {
        if ($this->getTotalDuration() <= 0) {
            return 0;
        }

        return $this->getEffectiveDuration() / $this->getTotalDuration();
    }

    /**
     * 计算异常率
     */
    public function getAnomalyRate(): float
    {
        if ($this->totalSessions <= 0) {
            return 0;
        }

        return $this->anomalyCount / $this->totalSessions;
    }

    /**
     * 获取统计摘要
     */
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'type' => $this->statisticsType->value,
            'typeLabel' => $this->statisticsType->getLabel(),
            'period' => $this->statisticsPeriod->value,
            'periodLabel' => $this->statisticsPeriod->getLabel(),
            'date' => $this->statisticsDate->format('Y-m-d'),
            'totalUsers' => $this->totalUsers,
            'activeUsers' => $this->activeUsers,
            'userActiveRate' => $this->getUserActiveRate(),
            'totalSessions' => $this->totalSessions,
            'totalDuration' => $this->getTotalDuration(),
            'effectiveDuration' => $this->getEffectiveDuration(),
            'learningEfficiency' => $this->getLearningEfficiency(),
            'anomalyCount' => $this->anomalyCount,
            'anomalyRate' => $this->getAnomalyRate(),
            'completionRate' => $this->getCompletionRate(),
            'averageEfficiency' => $this->getAverageEfficiency(),
        ];
    }

    /**
     * 格式化时长
     */
    public function getFormattedDuration(float $duration): string
    {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%d小时%d分钟', $hours, $minutes);
        }
        if ($minutes > 0) {
            return sprintf('%d分钟%d秒', $minutes, $seconds);
        }

        return sprintf('%.1f秒', $seconds);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->id,
            'statisticsType' => $this->statisticsType->value,
            'statisticsTypeLabel' => $this->statisticsType->getLabel(),
            'statisticsPeriod' => $this->statisticsPeriod->value,
            'statisticsPeriodLabel' => $this->statisticsPeriod->getLabel(),
            'statisticsDate' => $this->statisticsDate->format('Y-m-d'),
            'totalUsers' => $this->totalUsers,
            'activeUsers' => $this->activeUsers,
            'userActiveRate' => $this->getUserActiveRate(),
            'totalSessions' => $this->totalSessions,
            'totalDuration' => $this->getTotalDuration(),
            'effectiveDuration' => $this->getEffectiveDuration(),
            'formattedTotalDuration' => $this->getFormattedDuration($this->getTotalDuration()),
            'formattedEffectiveDuration' => $this->getFormattedDuration($this->getEffectiveDuration()),
            'learningEfficiency' => $this->getLearningEfficiency(),
            'anomalyCount' => $this->anomalyCount,
            'anomalyRate' => $this->getAnomalyRate(),
            'completionRate' => $this->getCompletionRate(),
            'averageEfficiency' => $this->getAverageEfficiency(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->id,
            'statisticsType' => $this->statisticsType->value,
            'statisticsTypeLabel' => $this->statisticsType->getLabel(),
            'statisticsPeriod' => $this->statisticsPeriod->value,
            'statisticsPeriodLabel' => $this->statisticsPeriod->getLabel(),
            'statisticsDate' => $this->statisticsDate->format('Y-m-d'),
            'userStatistics' => $this->userStatistics,
            'courseStatistics' => $this->courseStatistics,
            'behaviorStatistics' => $this->behaviorStatistics,
            'anomalyStatistics' => $this->anomalyStatistics,
            'deviceStatistics' => $this->deviceStatistics,
            'progressStatistics' => $this->progressStatistics,
            'durationStatistics' => $this->durationStatistics,
            'totalUsers' => $this->totalUsers,
            'activeUsers' => $this->activeUsers,
            'userActiveRate' => $this->getUserActiveRate(),
            'totalSessions' => $this->totalSessions,
            'totalDuration' => $this->getTotalDuration(),
            'effectiveDuration' => $this->getEffectiveDuration(),
            'formattedTotalDuration' => $this->getFormattedDuration($this->getTotalDuration()),
            'formattedEffectiveDuration' => $this->getFormattedDuration($this->getEffectiveDuration()),
            'learningEfficiency' => $this->getLearningEfficiency(),
            'anomalyCount' => $this->anomalyCount,
            'anomalyRate' => $this->getAnomalyRate(),
            'completionRate' => $this->getCompletionRate(),
            'averageEfficiency' => $this->getAverageEfficiency(),
            'extendedData' => $this->extendedData,
            'summary' => $this->getSummary(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '学习统计[%s] - 类型:%s 周期:%s 日期:%s',
            $this->id ?? '未知',
            $this->statisticsType->getLabel(),
            $this->statisticsPeriod->getLabel(),
            $this->statisticsDate->format('Y-m-d')
        );
    }
}
