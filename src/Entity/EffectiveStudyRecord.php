<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;

/**
 * 有效学时记录实体
 * 记录每个时间段的学时有效性判定结果，用于学时认定管理
 */
#[ORM\Entity(repositoryClass: EffectiveStudyRecordRepository::class)]
#[ORM\Table(name: 'job_training_effective_study_record', options: ['comment' => '有效学时记录'])]
#[ORM\Index(name: 'job_training_effective_study_record_idx_user_date', columns: ['user_id', 'study_date'])]
#[ORM\Index(name: 'job_training_effective_study_record_idx_session_time', columns: ['session_id', 'start_time', 'end_time'])]
#[ORM\Index(name: 'job_training_effective_study_record_idx_status_reason', columns: ['status', 'invalid_reason'])]
#[ORM\Index(name: 'job_training_effective_study_record_idx_course_lesson', columns: ['course_id', 'lesson_id'])]
class EffectiveStudyRecord implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: false, options: ['comment' => '主键ID'])]
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false, options: ['comment' => '用户ID（学员ID）'])]
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    private string $userId;

    /**
     * 学习会话
     */
    #[ORM\ManyToOne(targetEntity: LearnSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    private LearnSession $session;

    /**
     * 课程
     */
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    private Course $course;

    /**
     * 课时
     */
    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(name: 'lesson_id', referencedColumnName: 'id', nullable: false)]
    private Lesson $lesson;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: false, options: ['comment' => '学习日期'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $studyDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '开始时间'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '结束时间'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '总时长（秒）'])]
    #[Assert\PositiveOrZero]
    private float $totalDuration;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '有效时长（秒）'])]
    #[Assert\PositiveOrZero]
    #[IndexColumn]
    private float $effectiveDuration;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '无效时长（秒）'])]
    #[Assert\PositiveOrZero]
    private float $invalidDuration;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: StudyTimeStatus::class, options: ['comment' => '学时状态'])]
    #[Assert\Choice(callback: [StudyTimeStatus::class, 'cases'])]
    private StudyTimeStatus $status;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, enumType: InvalidTimeReason::class, options: ['comment' => '无效原因（如果状态为无效）'])]
    #[Assert\Choice(callback: [InvalidTimeReason::class, 'cases'])]
    private ?InvalidTimeReason $invalidReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '详细说明'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '学习质量评分（0-10分）'])]
    #[Assert\Range(min: 0, max: 10, notInRangeMessage: 'Quality score must be between 0 and 10.')]
    private ?float $qualityScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '专注度评分（0-1）'])]
    #[Assert\Range(min: 0, max: 1, notInRangeMessage: 'Focus score must be between 0 and 1.')]
    private ?float $focusScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '交互活跃度评分（0-1）'])]
    #[Assert\Range(min: 0, max: 1, notInRangeMessage: 'Interaction score must be between 0 and 1.')]
    private ?float $interactionScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '学习连续性评分（0-1）'])]
    #[Assert\Range(min: 0, max: 1, notInRangeMessage: 'Continuity score must be between 0 and 1.')]
    private ?float $continuityScore = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '证据数据（JSON格式）'])]
    #[Assert\Type(type: 'array', message: 'Evidence data must be an array.')]
    private ?array $evidenceData = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为统计数据（JSON格式）'])]
    #[Assert\Type(type: 'array', message: 'Behavior stats must be an array.')]
    private ?array $behaviorStats = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证结果数据（JSON格式）'])]
    #[Assert\Type(type: 'array', message: 'Validation result must be an array.')]
    private ?array $validationResult = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    #[Assert\Length(max: 65535)]
    private ?string $reviewComment = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '审核人'])]
    #[Assert\Length(max: 64)]
    private ?string $reviewedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审核时间'])]
    #[Assert\Type(type: '\DateTimeImmutable', message: 'Review time must be a valid DateTime.')]
    private ?\DateTimeImmutable $reviewTime = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '是否计入日累计时长', 'default' => true])]
    #[Assert\Type(type: 'bool', message: 'Include in daily total must be a boolean.')]
    private bool $includeInDailyTotal = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '是否已通知学员', 'default' => false])]
    #[Assert\Type(type: 'bool', message: 'Student notified must be a boolean.')]
    private bool $studentNotified = false;

    public function __construct()
    {
        $this->id = uniqid('esr_', true);
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
        $this->status = StudyTimeStatus::PENDING;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getSession(): LearnSession
    {
        return $this->session;
    }

    public function setSession(LearnSession $session): void
    {
        $this->session = $session;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): void
    {
        $this->lesson = $lesson;
    }

    public function getStudyDate(): \DateTimeImmutable
    {
        return $this->studyDate;
    }

    public function setStudyDate(\DateTimeImmutable $studyDate): void
    {
        $this->studyDate = $studyDate;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getTotalDuration(): float
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(float $totalDuration): void
    {
        $this->totalDuration = $totalDuration;
    }

    public function getEffectiveDuration(): float
    {
        return $this->effectiveDuration;
    }

    public function setEffectiveDuration(float $effectiveDuration): void
    {
        $this->effectiveDuration = $effectiveDuration;
    }

    public function getInvalidDuration(): float
    {
        return $this->invalidDuration;
    }

    public function setInvalidDuration(float $invalidDuration): void
    {
        $this->invalidDuration = $invalidDuration;
    }

    public function getStatus(): StudyTimeStatus
    {
        return $this->status;
    }

    public function setStatus(StudyTimeStatus $status): void
    {
        $this->status = $status;
    }

    public function getInvalidReason(): ?InvalidTimeReason
    {
        return $this->invalidReason;
    }

    public function setInvalidReason(?InvalidTimeReason $invalidReason): void
    {
        $this->invalidReason = $invalidReason;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getQualityScore(): ?float
    {
        return $this->qualityScore;
    }

    public function setQualityScore(?float $qualityScore): void
    {
        $this->qualityScore = $qualityScore;
    }

    public function getFocusScore(): ?float
    {
        return $this->focusScore;
    }

    public function setFocusScore(?float $focusScore): void
    {
        $this->focusScore = $focusScore;
    }

    public function getInteractionScore(): ?float
    {
        return $this->interactionScore;
    }

    public function setInteractionScore(?float $interactionScore): void
    {
        $this->interactionScore = $interactionScore;
    }

    public function getContinuityScore(): ?float
    {
        return $this->continuityScore;
    }

    public function setContinuityScore(?float $continuityScore): void
    {
        $this->continuityScore = $continuityScore;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getEvidenceData(): ?array
    {
        return $this->evidenceData;
    }

    /**
     * @param array<int, array<string, mixed>>|null $evidenceData
     */
    public function setEvidenceData(?array $evidenceData): void
    {
        $this->evidenceData = $evidenceData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBehaviorStats(): ?array
    {
        return $this->behaviorStats;
    }

    /**
     * @param array<string, mixed>|null $behaviorStats
     */
    public function setBehaviorStats(?array $behaviorStats): void
    {
        $this->behaviorStats = $behaviorStats;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getValidationResult(): ?array
    {
        return $this->validationResult;
    }

    /**
     * @param array<string, mixed>|null $validationResult
     */
    public function setValidationResult(?array $validationResult): void
    {
        $this->validationResult = $validationResult;
    }

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(?string $reviewComment): void
    {
        $this->reviewComment = $reviewComment;
    }

    public function getReviewedBy(): ?string
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?string $reviewedBy): void
    {
        $this->reviewedBy = $reviewedBy;
    }

    public function getReviewTime(): ?\DateTimeImmutable
    {
        return $this->reviewTime;
    }

    public function setReviewTime(?\DateTimeImmutable $reviewTime): void
    {
        $this->reviewTime = $reviewTime;
    }

    public function isIncludeInDailyTotal(): bool
    {
        return $this->includeInDailyTotal;
    }

    public function setIncludeInDailyTotal(bool $includeInDailyTotal): void
    {
        $this->includeInDailyTotal = $includeInDailyTotal;
    }

    public function isStudentNotified(): bool
    {
        return $this->studentNotified;
    }

    public function setStudentNotified(bool $studentNotified): void
    {
        $this->studentNotified = $studentNotified;
    }

    /**
     * 计算有效率
     */
    public function getEffectiveRate(): float
    {
        if (0.0 === $this->totalDuration) {
            return 0.0;
        }

        return $this->effectiveDuration / $this->totalDuration;
    }

    /**
     * 计算无效率
     */
    public function getInvalidRate(): float
    {
        if (0.0 === $this->totalDuration) {
            return 0.0;
        }

        return $this->invalidDuration / $this->totalDuration;
    }

    /**
     * 检查是否为高质量学习
     */
    public function isHighQuality(): bool
    {
        return null !== $this->qualityScore && $this->qualityScore >= 8.0;
    }

    /**
     * 检查是否需要审核
     */
    public function needsReview(): bool
    {
        return $this->status->needsReview();
    }

    /**
     * 标记为已审核
     */
    public function markAsReviewed(StudyTimeStatus $status, string $reviewedBy, ?string $comment = null): static
    {
        $this->status = $status;
        $this->reviewedBy = $reviewedBy;
        $this->reviewTime = new \DateTimeImmutable();
        $this->reviewComment = $comment;
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 设置为无效状态
     */
    public function markAsInvalid(InvalidTimeReason $reason, ?string $description = null): static
    {
        $this->status = StudyTimeStatus::INVALID;
        $this->invalidReason = $reason;
        $this->description = $description;
        $this->effectiveDuration = 0.0;
        $this->invalidDuration = $this->totalDuration;
        $this->includeInDailyTotal = false;
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 设置为有效状态
     */
    public function markAsValid(?float $effectiveDuration = null): static
    {
        $this->status = StudyTimeStatus::VALID;
        $this->invalidReason = null;
        $this->effectiveDuration = $effectiveDuration ?? $this->totalDuration;
        $this->invalidDuration = $this->totalDuration - $this->effectiveDuration;
        $this->includeInDailyTotal = true;
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 添加证据数据
     */
    /**
     * @param array<string, mixed> $data
     */
    public function addEvidence(string $type, array $data): static
    {
        if (null === $this->evidenceData) {
            $this->evidenceData = [];
        }

        $this->evidenceData[] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => time(),
        ];

        return $this;
    }

    /**
     * 获取格式化的时长
     */
    public function getFormattedDuration(float $duration): string
    {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * 获取学习效率描述
     */
    public function getEfficiencyDescription(): string
    {
        $rate = $this->getEffectiveRate();

        return match (true) {
            $rate >= 0.9 => '优秀',
            $rate >= 0.8 => '良好',
            $rate >= 0.6 => '一般',
            $rate >= 0.4 => '较差',
            default => '很差',
        };
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
