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
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;

/**
 * 学习异常记录实体
 *
 * 记录和管理学习过程中的异常情况，包括多设备登录、快速进度、
 * 窗口切换、空闲超时、人脸检测失败、网络异常等各种异常行为。
 */
/**
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnAnomalyRepository::class)]
#[ORM\Table(name: 'job_training_learn_anomaly', options: ['comment' => '学习异常记录'])]
#[ORM\Index(name: 'job_training_learn_anomaly_idx_session_anomaly', columns: ['session_id', 'anomaly_type'])]
#[ORM\Index(name: 'job_training_learn_anomaly_idx_severity_status', columns: ['severity', 'status'])]
class LearnAnomaly implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(targetEntity: LearnSession::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LearnSession $session;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: AnomalyType::class, options: ['comment' => '异常类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AnomalyType::class, 'cases'])]
    private AnomalyType $anomalyType;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $anomalyDescription = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $anomalyData = null;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: AnomalySeverity::class, options: ['comment' => '严重程度'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AnomalySeverity::class, 'cases'])]
    private AnomalySeverity $severity;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: AnomalyStatus::class, options: ['comment' => '状态', 'default' => 'detected'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AnomalyStatus::class, 'cases'])]
    private AnomalyStatus $status = AnomalyStatus::DETECTED;

    #[ORM\Column(options: ['comment' => '是否自动检测', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $isAutoDetected = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '解决方案'])]
    #[Assert\Length(max: 65535)]
    private ?string $resolution = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '解决人'])]
    #[Assert\Length(max: 100)]
    private ?string $resolvedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '检测时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $detectTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '解决时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $resolveTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true, options: ['comment' => '影响评分（0-10）'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Impact score must be a valid decimal')]
    private ?string $impactScore = null;

    /**
     * @var array<int|string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '相关证据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $evidence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $processingNotes = null;

    public function __construct()
    {
        $this->detectTime = new \DateTimeImmutable();
    }

    public function getSession(): LearnSession
    {
        return $this->session;
    }

    public function setSession(LearnSession $session): void
    {
        $this->session = $session;
    }

    public function getAnomalyType(): AnomalyType
    {
        return $this->anomalyType;
    }

    public function setAnomalyType(AnomalyType $anomalyType): void
    {
        $this->anomalyType = $anomalyType;
    }

    public function getAnomalyDescription(): ?string
    {
        return $this->anomalyDescription;
    }

    public function setAnomalyDescription(?string $anomalyDescription): void
    {
        $this->anomalyDescription = $anomalyDescription;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAnomalyData(): ?array
    {
        return $this->anomalyData;
    }

    /**
     * @param array<string, mixed>|null $anomalyData
     */
    public function setAnomalyData(?array $anomalyData): void
    {
        $this->anomalyData = $anomalyData;
    }

    public function getSeverity(): AnomalySeverity
    {
        return $this->severity;
    }

    public function setSeverity(AnomalySeverity $severity): void
    {
        $this->severity = $severity;
    }

    public function getStatus(): AnomalyStatus
    {
        return $this->status;
    }

    public function setStatus(AnomalyStatus $status): void
    {
        $this->status = $status;
    }

    public function isAutoDetected(): bool
    {
        return $this->isAutoDetected;
    }

    public function setIsAutoDetected(bool $isAutoDetected): void
    {
        $this->isAutoDetected = $isAutoDetected;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): void
    {
        $this->resolution = $resolution;
    }

    public function getResolvedBy(): ?string
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?string $resolvedBy): void
    {
        $this->resolvedBy = $resolvedBy;
    }

    public function getDetectTime(): ?\DateTimeImmutable
    {
        return $this->detectTime;
    }

    public function setDetectTime(?\DateTimeImmutable $detectTime): void
    {
        $this->detectTime = $detectTime;
    }

    public function getResolveTime(): ?\DateTimeImmutable
    {
        return $this->resolveTime;
    }

    public function setResolveTime(?\DateTimeImmutable $resolveTime): void
    {
        $this->resolveTime = $resolveTime;
    }

    public function getImpactScore(): ?float
    {
        return null !== $this->impactScore ? (float) $this->impactScore : null;
    }

    public function setImpactScore(?float $impactScore): void
    {
        $this->impactScore = null !== $impactScore ? (string) max(0, min(10, $impactScore)) : null;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getEvidence(): ?array
    {
        return $this->evidence;
    }

    /**
     * @param array<int|string, mixed>|null $evidence
     */
    public function setEvidence(?array $evidence): void
    {
        $this->evidence = $evidence;
    }

    public function getProcessingNotes(): ?string
    {
        return $this->processingNotes;
    }

    public function setProcessingNotes(?string $processingNotes): void
    {
        $this->processingNotes = $processingNotes;
    }

    /**
     * 标记为正在调查
     */
    public function markAsInvestigating(string $investigator): static
    {
        $this->setStatus(AnomalyStatus::INVESTIGATING);
        $this->setResolvedBy($investigator);

        return $this;
    }

    /**
     * 标记为已解决
     */
    public function markAsResolved(string $resolution, string $resolvedBy): static
    {
        $this->setStatus(AnomalyStatus::RESOLVED);
        $this->setResolution($resolution);
        $this->setResolvedBy($resolvedBy);
        $this->setResolveTime(new \DateTimeImmutable());

        return $this;
    }

    /**
     * 标记为忽略
     */
    public function markAsIgnored(string $reason, string $ignoredBy): static
    {
        $this->setStatus(AnomalyStatus::IGNORED);
        $this->setResolution($reason);
        $this->setResolvedBy($ignoredBy);
        $this->setResolveTime(new \DateTimeImmutable());

        return $this;
    }

    /**
     * 添加证据
     */
    /**
     * @param array<string, mixed> $data
     */
    public function addEvidence(string $type, array $data): static
    {
        $evidence = $this->evidence ?? [];

        $evidenceEntry = [
            'type' => $type,
            'data' => $data,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $evidence[] = $evidenceEntry;
        $this->setEvidence($evidence);

        return $this;
    }

    /**
     * 计算处理时长
     */
    public function getProcessingDuration(): ?int
    {
        if (null === $this->resolveTime || null === $this->detectTime) {
            return null;
        }

        return $this->resolveTime->getTimestamp() - $this->detectTime->getTimestamp();
    }

    /**
     * 检查是否为高优先级异常
     */
    public function isHighPriority(): bool
    {
        return in_array($this->severity, [AnomalySeverity::HIGH, AnomalySeverity::CRITICAL], true);
    }

    /**
     * 检查是否已处理
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED], true);
    }

    /**
     * 获取异常摘要
     */
    public function getSummary(): string
    {
        $sessionInfo = sprintf(
            '会话[%s] 学员[%s] 课时[%s]',
            $this->session->getId(),
            $this->session->getStudent()->getUserIdentifier(),
            $this->session->getLesson()->getTitle()
        );

        return sprintf(
            '%s: %s - %s',
            $this->anomalyType->getLabel(),
            $this->anomalyDescription ?? '无描述',
            $sessionInfo
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->session->getId(),
            'studentName' => $this->session->getStudent()->getUserIdentifier(),
            'lessonTitle' => $this->session->getLesson()->getTitle(),
            'anomalyType' => $this->anomalyType->value,
            'anomalyTypeLabel' => $this->anomalyType->getLabel(),
            'anomalyDescription' => $this->anomalyDescription,
            'severity' => $this->severity->value,
            'severityLabel' => $this->severity->getLabel(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->getLabel(),
            'isAutoDetected' => $this->isAutoDetected,
            'impactScore' => $this->getImpactScore(),
            'isHighPriority' => $this->isHighPriority(),
            'isProcessed' => $this->isProcessed(),
            'processingDuration' => $this->getProcessingDuration(),
            'detectTime' => $this->detectTime?->format('Y-m-d H:i:s'),
            'resolveTime' => $this->resolveTime?->format('Y-m-d H:i:s'),
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
            'sessionId' => $this->session->getId(),
            'studentName' => $this->session->getStudent()->getUserIdentifier(),
            'lessonTitle' => $this->session->getLesson()->getTitle(),
            'anomalyType' => $this->anomalyType->value,
            'anomalyTypeLabel' => $this->anomalyType->getLabel(),
            'anomalyDescription' => $this->anomalyDescription,
            'anomalyData' => $this->anomalyData,
            'severity' => $this->severity->value,
            'severityLabel' => $this->severity->getLabel(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->getLabel(),
            'isAutoDetected' => $this->isAutoDetected,
            'resolution' => $this->resolution,
            'resolvedBy' => $this->resolvedBy,
            'impactScore' => $this->getImpactScore(),
            'evidence' => $this->evidence,
            'processingNotes' => $this->processingNotes,
            'isHighPriority' => $this->isHighPriority(),
            'isProcessed' => $this->isProcessed(),
            'processingDuration' => $this->getProcessingDuration(),
            'summary' => $this->getSummary(),
            'detectTime' => $this->detectTime?->format('Y-m-d H:i:s'),
            'resolveTime' => $this->resolveTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
