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
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;

/**
 * 学习进度管理实体
 * 
 * 管理跨设备的学习进度同步和有效学习时长计算。
 * 支持多设备学习进度同步、有效时长统计、学习轨迹记录等功能。
 */
#[ORM\Entity(repositoryClass: LearnProgressRepository::class)]
#[ORM\Table(name: 'job_training_learn_progress', options: ['comment' => '学习进度管理'])]
#[ORM\UniqueConstraint(name: 'uniq_user_lesson', columns: ['user_id', 'lesson_id'])]
#[ORM\Index(name: 'idx_user_course', columns: ['user_id', 'course_id'])]
#[ORM\Index(name: 'idx_progress_status', columns: ['is_completed', 'progress'])]
#[ORM\Index(name: 'idx_last_update', columns: ['last_update_time'])]
class LearnProgress implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '用户ID'])]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Lesson $lesson;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '进度百分比（0-100）', 'default' => '0.00'])]
    private string $progress = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '已观看时长（秒）', 'default' => '0.0000'])]
    private string $watchedDuration = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '有效学习时长（秒）', 'default' => '0.0000'])]
    private string $effectiveDuration = '0.0000';

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '已观看片段JSON'])]
    private ?array $watchedSegments = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '进度历史JSON'])]
    private ?array $progressHistory = null;

    #[ORM\Column(options: ['comment' => '是否完成', 'default' => false])]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后更新时间'])]
    private ?\DateTimeImmutable $lastUpdateTime = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '最后更新设备指纹'])]
    private ?string $lastUpdateDevice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true, options: ['comment' => '学习质量评分（0-10）'])]
    private ?string $qualityScore = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '学习统计数据JSON'])]
    private ?array $learningStats = null;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): static
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getProgress(): float
    {
        return (float) $this->progress;
    }

    public function setProgress(float $progress): static
    {
        $this->progress = (string) max(0, min(100, $progress));
        return $this;
    }

    public function getWatchedDuration(): float
    {
        return (float) $this->watchedDuration;
    }

    public function setWatchedDuration(float $watchedDuration): static
    {
        $this->watchedDuration = (string) max(0, $watchedDuration);
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

    public function getWatchedSegments(): ?array
    {
        return $this->watchedSegments;
    }

    public function setWatchedSegments(?array $watchedSegments): static
    {
        $this->watchedSegments = $watchedSegments;
        return $this;
    }

    public function getProgressHistory(): ?array
    {
        return $this->progressHistory;
    }

    public function setProgressHistory(?array $progressHistory): static
    {
        $this->progressHistory = $progressHistory;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    public function getLastUpdateTime(): ?\DateTimeImmutable
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime(?\DateTimeImmutable $lastUpdateTime): static
    {
        $this->lastUpdateTime = $lastUpdateTime;
        return $this;
    }

    public function getLastUpdateDevice(): ?string
    {
        return $this->lastUpdateDevice;
    }

    public function setLastUpdateDevice(?string $lastUpdateDevice): static
    {
        $this->lastUpdateDevice = $lastUpdateDevice;
        return $this;
    }

    public function getQualityScore(): ?float
    {
        return $this->qualityScore !== null ? (float) $this->qualityScore : null;
    }

    public function setQualityScore(?float $qualityScore): static
    {
        $this->qualityScore = $qualityScore !== null ? (string) max(0, min(10, $qualityScore)) : null;
        return $this;
    }

    public function getLearningStats(): ?array
    {
        return $this->learningStats;
    }

    public function setLearningStats(?array $learningStats): static
    {
        $this->learningStats = $learningStats;
        return $this;
    }/**
     * 更新学习进度
     */
    public function updateProgress(float $progress, float $watchedDuration, ?string $deviceFingerprint = null): static
    {
        $now = new \DateTimeImmutable();
        
        // 记录进度历史
        $historyEntry = [
            'time' => $now->format('Y-m-d H:i:s'),
            'progress' => $progress,
            'watchedDuration' => $watchedDuration,
            'device' => $deviceFingerprint,
        ];
        
        $history = $this->progressHistory ?? [];
        $history[] = $historyEntry;
        
        // 只保留最近100条记录
        if ((bool) count($history) > 100) {
            $history = array_slice($history, -100);
        }
        
        $this->setProgress($progress);
        $this->setWatchedDuration($watchedDuration);
        $this->setProgressHistory($history);
        $this->setLastUpdateTime($now);
        
        if ($deviceFingerprint !== null) {
            $this->setLastUpdateDevice($deviceFingerprint);
        }
        
        // 自动标记完成状态
        if ($progress >= 100.0) {
            $this->setIsCompleted(true);
        }
        
        return $this;
    }

    /**
     * 添加观看片段
     */
    public function addWatchedSegment(float $start, float $end): static
    {
        $segments = $this->watchedSegments ?? [];
        
        $newSegment = [
            'start' => $start,
            'end' => $end,
            'duration' => $end - $start,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        $segments[] = $newSegment;
        $this->setWatchedSegments($segments);
        
        return $this;
    }

    /**
     * 计算有效学习时长
     */
    public function calculateEffectiveDuration(array $behaviorData = []): float
    {
        $segments = $this->watchedSegments ?? [];
        $totalDuration = 0;
        
        foreach ($segments as $segment) {
            $duration = $segment['duration'];
            
            // 基于行为数据调整有效时长
            // 这里可以根据暂停、切换窗口等行为减少有效时长
            $effectiveRatio = $this->calculateEffectiveRatio($segment, $behaviorData);
            $totalDuration += $duration * $effectiveRatio;
        }
        
        $this->setEffectiveDuration($totalDuration);
        return $totalDuration;
    }

    /**
     * 计算有效学习比率
     */
    private function calculateEffectiveRatio(array $segment, array $behaviorData): float
    {
        // 基础比率
        $ratio = 1.0;
        
        // 根据行为数据调整比率
        // 例如：频繁暂停、窗口切换等会降低有效比率
        
        return max(0.1, min(1.0, $ratio)); // 确保比率在0.1-1.0之间
    }

    /**
     * 获取学习效率
     */
    public function getLearningEfficiency(): float
    {
        $watchedDuration = $this->getWatchedDuration();
        if ($watchedDuration <= 0) {
            return 0;
        }
        
        return $this->getEffectiveDuration() / $watchedDuration;
    }

    /**
     * 检查是否需要同步
     */
    public function needsSync(\DateTimeImmutable $lastSyncTime): bool
    {
        return $this->lastUpdateTime !== null && $this->lastUpdateTime > $lastSyncTime;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'courseId' => $this->course->getId(),
            'courseTitle' => $this->course->getTitle(),
            'lessonId' => $this->lesson->getId(),
            'lessonTitle' => $this->lesson->getTitle(),
            'progress' => $this->getProgress(),
            'watchedDuration' => $this->getWatchedDuration(),
            'effectiveDuration' => $this->getEffectiveDuration(),
            'isCompleted' => $this->isCompleted,
            'qualityScore' => $this->getQualityScore(),
            'learningEfficiency' => $this->getLearningEfficiency(),
            'lastUpdateTime' => $this->lastUpdateTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'courseId' => $this->course->getId(),
            'courseTitle' => $this->course->getTitle(),
            'lessonId' => $this->lesson->getId(),
            'lessonTitle' => $this->lesson->getTitle(),
            'progress' => $this->getProgress(),
            'watchedDuration' => $this->getWatchedDuration(),
            'effectiveDuration' => $this->getEffectiveDuration(),
            'watchedSegments' => $this->watchedSegments,
            'progressHistory' => $this->progressHistory,
            'isCompleted' => $this->isCompleted,
            'lastUpdateTime' => $this->lastUpdateTime?->format('Y-m-d H:i:s'),
            'lastUpdateDevice' => $this->lastUpdateDevice,
            'qualityScore' => $this->getQualityScore(),
            'learningStats' => $this->learningStats,
            'learningEfficiency' => $this->getLearningEfficiency(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf('学习进度[%s] - 用户:%s 课程:%s 进度:%d%%', 
            $this->id ?? '未知',
            $this->userId ?? '未知',
            $this->course->getTitle(),
            $this->getProgress()
        );
    }
} 