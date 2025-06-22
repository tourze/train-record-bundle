<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Repository\LearnStatisticsRepository;

/**
 * 学习统计实体
 * 
 * 存储各种维度的学习统计数据，支持按日、周、月等不同周期统计。
 * 包括用户统计、课程统计、行为统计、异常统计、设备统计等。
 */
#[ORM\Entity(repositoryClass: LearnStatisticsRepository::class)]
#[ORM\Table(name: 'job_training_learn_statistics', options: ['comment' => '学习统计'])]
#[ORM\UniqueConstraint(name: 'uniq_type_period_date', columns: ['statistics_type', 'statistics_period', 'statistics_date'])]
#[ORM\Index(name: 'idx_type_period', columns: ['statistics_type', 'statistics_period'])]
#[ORM\Index(name: 'idx_statistics_date', columns: ['statistics_date'])]
class LearnStatistics implements ApiArrayInterface, AdminArrayInterface
{
    use TimestampableAware;
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[IndexColumn]
    #[ORM\Column(length: 30, enumType: StatisticsType::class, options: ['comment' => '统计类型'])]
    private StatisticsType $statisticsType;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: StatisticsPeriod::class, options: ['comment' => '统计周期'])]
    private StatisticsPeriod $statisticsPeriod;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => '统计日期'])]
    private \DateTimeImmutable $statisticsDate;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '用户统计JSON'])]
    private ?array $userStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '课程统计JSON'])]
    private ?array $courseStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为统计JSON'])]
    private ?array $behaviorStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常统计JSON'])]
    private ?array $anomalyStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '设备统计JSON'])]
    private ?array $deviceStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '进度统计JSON'])]
    private ?array $progressStatistics = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '时长统计JSON'])]
    private ?array $durationStatistics = null;

    #[ORM\Column(options: ['comment' => '总用户数', 'default' => 0])]
    private int $totalUsers = 0;

    #[ORM\Column(options: ['comment' => '活跃用户数', 'default' => 0])]
    private int $activeUsers = 0;

    #[ORM\Column(options: ['comment' => '总会话数', 'default' => 0])]
    private int $totalSessions = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, options: ['comment' => '总学习时长（秒）', 'default' => '0.0000'])]
    private string $totalDuration = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, options: ['comment' => '有效学习时长（秒）', 'default' => '0.0000'])]
    private string $effectiveDuration = '0.0000';

    #[ORM\Column(options: ['comment' => '异常数量', 'default' => 0])]
    private int $anomalyCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '完成率（%）'])]
    private ?string $completionRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 4, nullable: true, options: ['comment' => '平均学习效率'])]
    private ?string $averageEfficiency = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展数据JSON'])]
    private ?array $extendedData = null;


    public function __construct()
    {
        $this->statisticsDate = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStatisticsType(): StatisticsType
    {
        return $this->statisticsType;
    }

    public function setStatisticsType(StatisticsType $statisticsType): static
    {
        $this->statisticsType = $statisticsType;
        return $this;
    }

    public function getStatisticsPeriod(): StatisticsPeriod
    {
        return $this->statisticsPeriod;
    }

    public function setStatisticsPeriod(StatisticsPeriod $statisticsPeriod): static
    {
        $this->statisticsPeriod = $statisticsPeriod;
        return $this;
    }

    public function getStatisticsDate(): \DateTimeImmutable
    {
        return $this->statisticsDate;
    }

    public function setStatisticsDate(\DateTimeImmutable $statisticsDate): static
    {
        $this->statisticsDate = $statisticsDate;
        return $this;
    }

    public function getUserStatistics(): ?array
    {
        return $this->userStatistics;
    }

    public function setUserStatistics(?array $userStatistics): static
    {
        $this->userStatistics = $userStatistics;
        return $this;
    }

    public function getCourseStatistics(): ?array
    {
        return $this->courseStatistics;
    }

    public function setCourseStatistics(?array $courseStatistics): static
    {
        $this->courseStatistics = $courseStatistics;
        return $this;
    }

    public function getBehaviorStatistics(): ?array
    {
        return $this->behaviorStatistics;
    }

    public function setBehaviorStatistics(?array $behaviorStatistics): static
    {
        $this->behaviorStatistics = $behaviorStatistics;
        return $this;
    }

    public function getAnomalyStatistics(): ?array
    {
        return $this->anomalyStatistics;
    }

    public function setAnomalyStatistics(?array $anomalyStatistics): static
    {
        $this->anomalyStatistics = $anomalyStatistics;
        return $this;
    }

    public function getDeviceStatistics(): ?array
    {
        return $this->deviceStatistics;
    }

    public function setDeviceStatistics(?array $deviceStatistics): static
    {
        $this->deviceStatistics = $deviceStatistics;
        return $this;
    }

    public function getProgressStatistics(): ?array
    {
        return $this->progressStatistics;
    }

    public function setProgressStatistics(?array $progressStatistics): static
    {
        $this->progressStatistics = $progressStatistics;
        return $this;
    }

    public function getDurationStatistics(): ?array
    {
        return $this->durationStatistics;
    }

    public function setDurationStatistics(?array $durationStatistics): static
    {
        $this->durationStatistics = $durationStatistics;
        return $this;
    }

    public function getTotalUsers(): int
    {
        return $this->totalUsers;
    }

    public function setTotalUsers(int $totalUsers): static
    {
        $this->totalUsers = max(0, $totalUsers);
        return $this;
    }

    public function getActiveUsers(): int
    {
        return $this->activeUsers;
    }

    public function setActiveUsers(int $activeUsers): static
    {
        $this->activeUsers = max(0, $activeUsers);
        return $this;
    }

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function setTotalSessions(int $totalSessions): static
    {
        $this->totalSessions = max(0, $totalSessions);
        return $this;
    }

    public function getTotalDuration(): float
    {
        return (float) $this->totalDuration;
    }

    public function setTotalDuration(float $totalDuration): static
    {
        $this->totalDuration = (string) max(0, $totalDuration);
        return $this;
    }

    public function getEffectiveDuration(): float
    {
        return (float) $this->effectiveDuration;
    }

    public function setEffectiveDuration(float $effectiveDuration): static
    {
        $this->effectiveDuration = (string) max(0, $effectiveDuration);
        return $this;
    }

    public function getAnomalyCount(): int
    {
        return $this->anomalyCount;
    }

    public function setAnomalyCount(int $anomalyCount): static
    {
        $this->anomalyCount = max(0, $anomalyCount);
        return $this;
    }

    public function getCompletionRate(): ?float
    {
        return $this->completionRate ? (float) $this->completionRate : null;
    }

    public function setCompletionRate(?float $completionRate): static
    {
        $this->completionRate = $completionRate !== null ? (string) max(0, min(100, $completionRate)) : null;
        return $this;
    }

    public function getAverageEfficiency(): ?float
    {
        return $this->averageEfficiency ? (float) $this->averageEfficiency : null;
    }

    public function setAverageEfficiency(?float $averageEfficiency): static
    {
        $this->averageEfficiency = $averageEfficiency !== null ? (string) max(0, min(1, $averageEfficiency)) : null;
        return $this;
    }

    public function getExtendedData(): ?array
    {
        return $this->extendedData;
    }

    public function setExtendedData(?array $extendedData): static
    {
        $this->extendedData = $extendedData;
        return $this;
    }/**
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
        } elseif ($minutes > 0) {
            return sprintf('%d分钟%d秒', $minutes, $seconds);
        } else {
            return sprintf('%.1f秒', $seconds);
        }
    }

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
} 