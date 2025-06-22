<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
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
#[ORM\Index(name: 'idx_user_date', columns: ['user_id', 'study_date'])]
#[ORM\Index(name: 'idx_session_time', columns: ['session_id', 'start_time', 'end_time'])]
#[ORM\Index(name: 'idx_status_reason', columns: ['status', 'invalid_reason'])]
#[ORM\Index(name: 'idx_course_lesson', columns: ['course_id', 'lesson_id'])]
#[ORM\Index(name: 'idx_effective_duration', columns: ['effective_duration'])]
class EffectiveStudyRecord implements Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: false, options: ['comment' => '主键ID'])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false, options: ['comment' => '用户ID（学员ID）'])]
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
    private \DateTimeImmutable $studyDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '开始时间'])]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false, options: ['comment' => '结束时间'])]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '总时长（秒）'])]
    private float $totalDuration;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '有效时长（秒）'])]
    private float $effectiveDuration;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['comment' => '无效时长（秒）'])]
    private float $invalidDuration;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: StudyTimeStatus::class, options: ['comment' => '学时状态'])]
    private StudyTimeStatus $status;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, enumType: InvalidTimeReason::class, options: ['comment' => '无效原因（如果状态为无效）'])]
    private ?InvalidTimeReason $invalidReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '详细说明'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '学习质量评分（0-10分）'])]
    private ?float $qualityScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '专注度评分（0-1）'])]
    private ?float $focusScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '交互活跃度评分（0-1）'])]
    private ?float $interactionScore = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '学习连续性评分（0-1）'])]
    private ?float $continuityScore = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '证据数据（JSON格式）'])]
    private ?array $evidenceData = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为统计数据（JSON格式）'])]
    private ?array $behaviorStats = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证结果数据（JSON格式）'])]
    private ?array $validationResult = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核意见'])]
    private ?string $reviewComment = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '审核人'])]
    private ?string $reviewedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '审核时间'])]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '是否计入日累计时长', 'default' => true])]
    private bool $includeInDailyTotal = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '是否已通知学员', 'default' => false])]
    private bool $studentNotified = false;

    public function __construct()
    {
        $this->id = uniqid('esr_', true);
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

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

    public function getSession(): LearnSession
    {
        return $this->session;
    }

    public function setSession(LearnSession $session): static
    {
        $this->session = $session;
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

    public function getStudyDate(): \DateTimeImmutable
    {
        return $this->studyDate;
    }

    public function setStudyDate(\DateTimeImmutable $studyDate): static
    {
        $this->studyDate = $studyDate;
        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getTotalDuration(): float
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(float $totalDuration): static
    {
        $this->totalDuration = $totalDuration;
        return $this;
    }

    public function getEffectiveDuration(): float
    {
        return $this->effectiveDuration;
    }

    public function setEffectiveDuration(float $effectiveDuration): static
    {
        $this->effectiveDuration = $effectiveDuration;
        return $this;
    }

    public function getInvalidDuration(): float
    {
        return $this->invalidDuration;
    }

    public function setInvalidDuration(float $invalidDuration): static
    {
        $this->invalidDuration = $invalidDuration;
        return $this;
    }

    public function getStatus(): StudyTimeStatus
    {
        return $this->status;
    }

    public function setStatus(StudyTimeStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getInvalidReason(): ?InvalidTimeReason
    {
        return $this->invalidReason;
    }

    public function setInvalidReason(?InvalidTimeReason $invalidReason): static
    {
        $this->invalidReason = $invalidReason;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getQualityScore(): ?float
    {
        return $this->qualityScore;
    }

    public function setQualityScore(?float $qualityScore): static
    {
        $this->qualityScore = $qualityScore;
        return $this;
    }

    public function getFocusScore(): ?float
    {
        return $this->focusScore;
    }

    public function setFocusScore(?float $focusScore): static
    {
        $this->focusScore = $focusScore;
        return $this;
    }

    public function getInteractionScore(): ?float
    {
        return $this->interactionScore;
    }

    public function setInteractionScore(?float $interactionScore): static
    {
        $this->interactionScore = $interactionScore;
        return $this;
    }

    public function getContinuityScore(): ?float
    {
        return $this->continuityScore;
    }

    public function setContinuityScore(?float $continuityScore): static
    {
        $this->continuityScore = $continuityScore;
        return $this;
    }

    public function getEvidenceData(): ?array
    {
        return $this->evidenceData;
    }

    public function setEvidenceData(?array $evidenceData): static
    {
        $this->evidenceData = $evidenceData;
        return $this;
    }

    public function getBehaviorStats(): ?array
    {
        return $this->behaviorStats;
    }

    public function setBehaviorStats(?array $behaviorStats): static
    {
        $this->behaviorStats = $behaviorStats;
        return $this;
    }

    public function getValidationResult(): ?array
    {
        return $this->validationResult;
    }

    public function setValidationResult(?array $validationResult): static
    {
        $this->validationResult = $validationResult;
        return $this;
    }

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function setReviewComment(?string $reviewComment): static
    {
        $this->reviewComment = $reviewComment;
        return $this;
    }

    public function getReviewedBy(): ?string
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?string $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;
        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
        return $this;
    }

    public function isIncludeInDailyTotal(): bool
    {
        return $this->includeInDailyTotal;
    }

    public function setIncludeInDailyTotal(bool $includeInDailyTotal): static
    {
        $this->includeInDailyTotal = $includeInDailyTotal;
        return $this;
    }

    public function isStudentNotified(): bool
    {
        return $this->studentNotified;
    }

    public function setStudentNotified(bool $studentNotified): static
    {
        $this->studentNotified = $studentNotified;
        return $this;
    }

    /**
     * 计算有效率
     */
    public function getEffectiveRate(): float
    {
        if ($this->totalDuration === 0.0) {
            return 0.0;
        }
        return $this->effectiveDuration / $this->totalDuration;
    }

    /**
     * 计算无效率
     */
    public function getInvalidRate(): float
    {
        if ($this->totalDuration === 0.0) {
            return 0.0;
        }
        return $this->invalidDuration / $this->totalDuration;
    }

    /**
     * 检查是否为高质量学习
     */
    public function isHighQuality(): bool
    {
        return $this->qualityScore !== null && $this->qualityScore >= 8.0;
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
        $this->reviewedAt = new \DateTimeImmutable();
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
    public function addEvidence(string $type, array $data): static
    {
        if ($this->evidenceData === null) {
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