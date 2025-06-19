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
use Tourze\EasyAdmin\Attribute\Action\Exportable;
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
#[Exportable]
#[ORM\Entity(repositoryClass: LearnAnomalyRepository::class)]
#[ORM\Table(name: 'job_training_learn_anomaly', options: ['comment' => '学习异常记录'])]
#[ORM\Index(name: 'idx_session_anomaly', columns: ['session_id', 'anomaly_type'])]
#[ORM\Index(name: 'idx_severity_status', columns: ['severity', 'status'])]
#[ORM\Index(name: 'idx_detected_time', columns: ['detected_time'])]
#[ORM\Index(name: 'idx_auto_detected', columns: ['is_auto_detected'])]
class LearnAnomaly implements ApiArrayInterface, AdminArrayInterface
{
    use TimestampableAware;
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: LearnSession::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LearnSession $session;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: AnomalyType::class, options: ['comment' => '异常类型'])]
    private AnomalyType $anomalyType;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '异常描述'])]
    private ?string $anomalyDescription = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常数据JSON'])]
    private ?array $anomalyData = null;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: AnomalySeverity::class, options: ['comment' => '严重程度'])]
    private AnomalySeverity $severity;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: AnomalyStatus::class, options: ['comment' => '状态', 'default' => 'detected'])]
    private AnomalyStatus $status = AnomalyStatus::DETECTED;

    #[ORM\Column(options: ['comment' => '是否自动检测', 'default' => true])]
    private bool $isAutoDetected = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '解决方案'])]
    private ?string $resolution = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '解决人'])]
    private ?string $resolvedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '检测时间'])]
    private ?\DateTimeImmutable $detectedTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '解决时间'])]
    private ?\DateTimeImmutable $resolvedTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true, options: ['comment' => '影响评分（0-10）'])]
    private ?string $impactScore = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '相关证据JSON'])]
    private ?array $evidence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理备注'])]
    private ?string $processingNotes = null;


    public function __construct()
    {
        $this->detectedTime = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getAnomalyType(): AnomalyType
    {
        return $this->anomalyType;
    }

    public function setAnomalyType(AnomalyType $anomalyType): static
    {
        $this->anomalyType = $anomalyType;
        return $this;
    }

    public function getAnomalyDescription(): ?string
    {
        return $this->anomalyDescription;
    }

    public function setAnomalyDescription(?string $anomalyDescription): static
    {
        $this->anomalyDescription = $anomalyDescription;
        return $this;
    }

    public function getAnomalyData(): ?array
    {
        return $this->anomalyData;
    }

    public function setAnomalyData(?array $anomalyData): static
    {
        $this->anomalyData = $anomalyData;
        return $this;
    }

    public function getSeverity(): AnomalySeverity
    {
        return $this->severity;
    }

    public function setSeverity(AnomalySeverity $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    public function getStatus(): AnomalyStatus
    {
        return $this->status;
    }

    public function setStatus(AnomalyStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isAutoDetected(): bool
    {
        return $this->isAutoDetected;
    }

    public function setIsAutoDetected(bool $isAutoDetected): static
    {
        $this->isAutoDetected = $isAutoDetected;
        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function getResolvedBy(): ?string
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?string $resolvedBy): static
    {
        $this->resolvedBy = $resolvedBy;
        return $this;
    }

    public function getDetectedTime(): ?\DateTimeImmutable
    {
        return $this->detectedTime;
    }

    public function setDetectedTime(?\DateTimeImmutable $detectedTime): static
    {
        $this->detectedTime = $detectedTime;
        return $this;
    }

    public function getResolvedTime(): ?\DateTimeImmutable
    {
        return $this->resolvedTime;
    }

    public function setResolvedTime(?\DateTimeImmutable $resolvedTime): static
    {
        $this->resolvedTime = $resolvedTime;
        return $this;
    }

    public function getImpactScore(): ?float
    {
        return $this->impactScore ? (float) $this->impactScore : null;
    }

    public function setImpactScore(?float $impactScore): static
    {
        $this->impactScore = $impactScore !== null ? (string) max(0, min(10, $impactScore)) : null;
        return $this;
    }

    public function getEvidence(): ?array
    {
        return $this->evidence;
    }

    public function setEvidence(?array $evidence): static
    {
        $this->evidence = $evidence;
        return $this;
    }

    public function getProcessingNotes(): ?string
    {
        return $this->processingNotes;
    }

    public function setProcessingNotes(?string $processingNotes): static
    {
        $this->processingNotes = $processingNotes;
        return $this;
    }/**
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
        $this->setResolvedTime(new \DateTimeImmutable());
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
        $this->setResolvedTime(new \DateTimeImmutable());
        return $this;
    }

    /**
     * 添加证据
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
        if (!$this->resolvedTime || !$this->detectedTime) {
            return null;
        }
        
        return $this->resolvedTime->getTimestamp() - $this->detectedTime->getTimestamp();
    }

    /**
     * 检查是否为高优先级异常
     */
    public function isHighPriority(): bool
    {
        return in_array($this->severity, [AnomalySeverity::HIGH, AnomalySeverity::CRITICAL]);
    }

    /**
     * 检查是否已处理
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED]);
    }

    /**
     * 获取异常摘要
     */
    public function getSummary(): string
    {
        $sessionInfo = sprintf(
            '会话[%s] 学员[%s] 课时[%s]',
            $this->session->getId(),
            $this->session->getStudent()->getRealName() ?? '未知',
            $this->session->getLesson()->getTitle()
        );
        
        return sprintf(
            '%s: %s - %s',
            $this->anomalyType->getLabel(),
            $this->anomalyDescription ?? '无描述',
            $sessionInfo
        );
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->session->getId(),
            'studentName' => $this->session->getStudent()->getRealName() ?? '未知',
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
            'detectedTime' => $this->detectedTime?->format('Y-m-d H:i:s'),
            'resolvedTime' => $this->resolvedTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->session->getId(),
            'studentName' => $this->session->getStudent()->getRealName() ?? '未知',
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
            'detectedTime' => $this->detectedTime?->format('Y-m-d H:i:s'),
            'resolvedTime' => $this->resolvedTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
} 