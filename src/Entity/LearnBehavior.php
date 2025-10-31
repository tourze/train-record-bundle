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
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;

/**
 * 学习行为记录实体
 *
 * 记录学习过程中的所有用户行为，用于防作弊检测和学习分析。
 * 包括视频控制、窗口焦点、鼠标键盘活动、网络状态等各种行为。
 */
/**
 * @implements AdminArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnBehaviorRepository::class)]
#[ORM\Table(name: 'job_training_learn_behavior', options: ['comment' => '学习行为记录'])]
#[ORM\Index(name: 'job_training_learn_behavior_idx_session_behavior_time', columns: ['session_id', 'behavior_type', 'create_time'])]
#[ORM\Index(name: 'job_training_learn_behavior_idx_suspicious', columns: ['is_suspicious', 'create_time'])]
class LearnBehavior implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use CreateTimeAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(targetEntity: LearnSession::class, inversedBy: 'learnBehaviors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LearnSession $session;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '用户ID（冗余字段，便于查询）'])]
    #[Assert\Length(max: 20)]
    private ?string $userId = null;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: BehaviorType::class, options: ['comment' => '行为类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [BehaviorType::class, 'cases'])]
    private BehaviorType $behaviorType;

    /**
     * @var array<string, mixed>|null
     */
    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $behaviorData = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '视频时间戳（秒）'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Video timestamp must be a valid decimal')]
    #[IndexColumn]
    private ?string $videoTimestamp = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '设备指纹'])]
    #[Assert\Length(max: 128)]
    private ?string $deviceFingerprint = null;

    #[ORM\Column(length: 45, nullable: true, options: ['comment' => 'IP地址'])]
    #[Assert\Length(max: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'User-Agent'])]
    #[Assert\Length(max: 65535)]
    private ?string $userAgent = null;

    #[ORM\Column(options: ['comment' => '是否可疑行为', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isSuspicious = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '可疑原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $suspiciousReason = null;

    /**
     * @var array<string, mixed>|null
     */
    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    public function getSession(): LearnSession
    {
        return $this->session;
    }

    public function setSession(LearnSession $session): void
    {
        $this->session = $session;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getBehaviorType(): BehaviorType
    {
        return $this->behaviorType;
    }

    public function setBehaviorType(BehaviorType $behaviorType): void
    {
        $this->behaviorType = $behaviorType;

        // 自动判断是否为可疑行为
        if ($behaviorType->isSuspicious()) {
            $this->isSuspicious = true;
            $this->suspiciousReason = '系统自动检测：' . $behaviorType->getLabel();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBehaviorData(): ?array
    {
        return $this->behaviorData;
    }

    /**
     * @param array<string, mixed>|null $behaviorData
     */
    public function setBehaviorData(?array $behaviorData): void
    {
        $this->behaviorData = $behaviorData;
    }

    public function getVideoTimestamp(): ?string
    {
        return $this->videoTimestamp;
    }

    public function setVideoTimestamp(?string $videoTimestamp): void
    {
        $this->videoTimestamp = $videoTimestamp;
    }

    public function getDeviceFingerprint(): ?string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(?string $deviceFingerprint): void
    {
        $this->deviceFingerprint = $deviceFingerprint;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function isSuspicious(): bool
    {
        return $this->isSuspicious;
    }

    public function getIsSuspicious(): bool
    {
        return $this->isSuspicious;
    }

    public function setIsSuspicious(bool $isSuspicious): void
    {
        $this->isSuspicious = $isSuspicious;
    }

    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    public function setSuspiciousReason(?string $suspiciousReason): void
    {
        $this->suspiciousReason = $suspiciousReason;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * 获取行为分类
     */
    public function getBehaviorCategory(): string
    {
        return $this->behaviorType->getCategory();
    }

    /**
     * 检查是否为焦点相关行为
     */
    public function isFocusRelated(): bool
    {
        return in_array($this->behaviorType, [
            BehaviorType::WINDOW_FOCUS,
            BehaviorType::WINDOW_BLUR,
            BehaviorType::PAGE_VISIBLE,
            BehaviorType::PAGE_HIDDEN,
        ], true);
    }

    /**
     * 检查是否为视频控制行为
     */
    public function isVideoControl(): bool
    {
        return 'video_control' === $this->getBehaviorCategory();
    }

    /**
     * 检查是否为空闲相关行为
     */
    public function isIdleRelated(): bool
    {
        return 'idle_detection' === $this->getBehaviorCategory();
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
            'id' => $this->getId(),
            'sessionId' => $this->getSession()->getId(),
            'behaviorType' => $this->getBehaviorType()->value,
            'behaviorLabel' => $this->getBehaviorType()->getLabel(),
            'behaviorCategory' => $this->getBehaviorCategory(),
            'behaviorData' => $this->getBehaviorData(),
            'videoTimestamp' => $this->getVideoTimestamp(),
            'deviceFingerprint' => $this->getDeviceFingerprint(),
            'ipAddress' => $this->getIpAddress(),
            'isSuspicious' => $this->isSuspicious(),
            'suspiciousReason' => $this->getSuspiciousReason(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'sessionId' => $this->getSession()->getId(),
            'studentName' => $this->getSession()->getStudent()->getUserIdentifier(),
            'lessonTitle' => $this->getSession()->getLesson()->getTitle(),
            'behaviorType' => $this->getBehaviorType()->value,
            'behaviorLabel' => $this->getBehaviorType()->getLabel(),
            'behaviorCategory' => $this->getBehaviorCategory(),
            'behaviorData' => $this->getBehaviorData(),
            'videoTimestamp' => $this->getVideoTimestamp(),
            'deviceFingerprint' => $this->getDeviceFingerprint(),
            'ipAddress' => $this->getIpAddress(),
            'isSuspicious' => $this->isSuspicious(),
            'suspiciousReason' => $this->getSuspiciousReason(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '学习行为[%s] - 类型:%s 时间:%s',
            $this->id ?? '未知',
            $this->behaviorType->getLabel(),
            $this->createTime?->format('Y-m-d H:i:s') ?? '未知'
        );
    }
}
