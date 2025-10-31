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
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;

/**
 * 学习进度管理实体
 *
 * 管理跨设备的学习进度同步和有效学习时长计算。
 * 支持多设备学习进度同步、有效时长统计、学习轨迹记录等功能。
 *
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnProgressRepository::class)]
#[ORM\Table(name: 'job_training_learn_progress', options: ['comment' => '学习进度管理'])]
#[ORM\UniqueConstraint(name: 'uniq_user_lesson', columns: ['user_id', 'lesson_id'])]
#[ORM\Index(name: 'job_training_learn_progress_idx_user_course', columns: ['user_id', 'course_id'])]
#[ORM\Index(name: 'job_training_learn_progress_idx_progress_status', columns: ['is_completed', 'progress'])]
class LearnProgress implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Lesson $lesson;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '进度百分比（0-100）', 'default' => '0.00'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Progress must be a valid decimal')]
    private string $progress = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '已观看时长（秒）', 'default' => '0.0000'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Watched duration must be a valid decimal')]
    private string $watchedDuration = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '有效学习时长（秒）', 'default' => '0.0000'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Effective duration must be a valid decimal')]
    private string $effectiveDuration = '0.0000';

    /**
     * @var array<int, array{start: float, end: float, duration: float, timestamp: string}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '已观看片段JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $watchedSegments = null;

    /**
     * @var array<int, array{time: string, progress: float, watchedDuration: float, device: string|null}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '进度历史JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $progressHistory = null;

    #[ORM\Column(options: ['comment' => '是否完成', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后更新时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $lastUpdateTime = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '最后更新设备指纹'])]
    #[Assert\Length(max: 128)]
    private ?string $lastUpdateDevice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true, options: ['comment' => '学习质量评分（0-10）'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Quality score must be a valid decimal')]
    private ?string $qualityScore = null;

    /**
     * @var array<string, mixed>|null
     */
    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '学习统计数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $learningStats = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '最后观看位置（秒）'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Last position must be a valid decimal')]
    private ?string $lastPosition = null;

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    /**
     * @deprecated This method is deprecated and should not be used. Use setCourse() instead.
     */
    public function setCourseId(string $courseId): void
    {
        // This method is deprecated because it tries to assign invalid types to typed properties
        // For now, just store the courseId in a temporary way without breaking type safety
        // In tests, use proper Course entities instead
        throw new UnsupportedActionException('setCourseId() is deprecated. Use setCourse() with a proper Course entity instead.');
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): void
    {
        $this->lesson = $lesson;
    }

    /**
     * @deprecated This method is deprecated and should not be used. Use setLesson() instead.
     */
    public function setLessonId(string $lessonId): void
    {
        // This method is deprecated because it tries to assign invalid types to typed properties
        // For now, just store the lessonId in a temporary way without breaking type safety
        // In tests, use proper Lesson entities instead
        throw new UnsupportedActionException('setLessonId() is deprecated. Use setLesson() with a proper Lesson entity instead.');
    }

    public function getProgress(): float
    {
        return (float) $this->progress;
    }

    public function setProgress(float $progress): void
    {
        $this->progress = (string) max(0, min(100, $progress));
    }

    public function getWatchedDuration(): float
    {
        return (float) $this->watchedDuration;
    }

    public function setWatchedDuration(float $watchedDuration): void
    {
        $this->watchedDuration = (string) max(0, $watchedDuration);
    }

    public function getEffectiveDuration(): float
    {
        return (float) $this->effectiveDuration;
    }

    public function setEffectiveDuration(float $effectiveDuration): void
    {
        $this->effectiveDuration = (string) max(0, $effectiveDuration);
    }

    /**
     * @return array<int, array{start: float, end: float, duration: float, timestamp: string}>|null
     */
    public function getWatchedSegments(): ?array
    {
        return $this->watchedSegments;
    }

    /**
     * @param array<int, array{start: float, end: float, duration: float, timestamp: string}>|null $watchedSegments
     */
    public function setWatchedSegments(?array $watchedSegments): void
    {
        $this->watchedSegments = $watchedSegments;
    }

    /**
     * @return array<int, array{time: string, progress: float, watchedDuration: float, device: string|null}>|null
     */
    public function getProgressHistory(): ?array
    {
        return $this->progressHistory;
    }

    /**
     * @param array<int, array{time: string, progress: float, watchedDuration: float, device: string|null}>|null $progressHistory
     */
    public function setProgressHistory(?array $progressHistory): void
    {
        $this->progressHistory = $progressHistory;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function getIsCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): void
    {
        $this->isCompleted = $isCompleted;
    }

    public function getLastUpdateTime(): ?\DateTimeImmutable
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime(?\DateTimeImmutable $lastUpdateTime): void
    {
        $this->lastUpdateTime = $lastUpdateTime;
    }

    public function getLastUpdateDevice(): ?string
    {
        return $this->lastUpdateDevice;
    }

    public function setLastUpdateDevice(?string $lastUpdateDevice): void
    {
        $this->lastUpdateDevice = $lastUpdateDevice;
    }

    public function getQualityScore(): ?float
    {
        return null !== $this->qualityScore ? (float) $this->qualityScore : null;
    }

    public function setQualityScore(?float $qualityScore): void
    {
        $this->qualityScore = null !== $qualityScore ? (string) max(0, min(10, $qualityScore)) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLearningStats(): ?array
    {
        return $this->learningStats;
    }

    /**
     * @param array<string, mixed>|null $learningStats
     */
    public function setLearningStats(?array $learningStats): void
    {
        $this->learningStats = $learningStats;
    }

    public function getLastPosition(): ?float
    {
        return null !== $this->lastPosition ? (float) $this->lastPosition : null;
    }

    public function setLastPosition(?float $lastPosition): void
    {
        $this->lastPosition = null !== $lastPosition ? (string) max(0, $lastPosition) : null;
    }

    /**
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
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        $this->setProgress($progress);
        $this->setWatchedDuration($watchedDuration);
        $this->setProgressHistory($history);
        $this->setLastUpdateTime($now);

        if (null !== $deviceFingerprint) {
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
    /**
     * @param array<string, mixed> $behaviorData
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
    /**
     * @param array{start: float, end: float, duration: float, timestamp: string} $segment
     * @param array<string, mixed> $behaviorData
     */
    /**
     * @param array<string, mixed> $segment
     * @param array<string, mixed> $behaviorData
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
        return null !== $this->lastUpdateTime && $this->lastUpdateTime > $lastSyncTime;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
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
        return sprintf(
            '学习进度[%s] - 用户:%s 课程:%s 进度:%d%%',
            $this->id ?? '未知',
            $this->userId ?? '未知',
            $this->course->getTitle(),
            $this->getProgress()
        );
    }
}
