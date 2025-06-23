<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;

/**
 * 学习行为记录实体
 * 
 * 记录学习过程中的所有用户行为，用于防作弊检测和学习分析。
 * 包括视频控制、窗口焦点、鼠标键盘活动、网络状态等各种行为。
 */
#[ORM\Entity(repositoryClass: LearnBehaviorRepository::class)]
#[ORM\Table(name: 'job_training_learn_behavior', options: ['comment' => '学习行为记录'])]
#[ORM\Index(name: 'idx_session_behavior_time', columns: ['session_id', 'behavior_type', 'create_time'])]
#[ORM\Index(name: 'idx_suspicious', columns: ['is_suspicious', 'create_time'])]
#[ORM\Index(name: 'idx_video_timestamp', columns: ['video_timestamp'])]
class LearnBehavior implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: LearnSession::class, inversedBy: 'learnBehaviors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LearnSession $session;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: BehaviorType::class, options: ['comment' => '行为类型'])]
    private BehaviorType $behaviorType;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为数据JSON'])]
    private ?array $behaviorData = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '视频时间戳（秒）'])]
    private ?string $videoTimestamp = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '设备指纹'])]
    private ?string $deviceFingerprint = null;

    #[ORM\Column(length: 45, nullable: true, options: ['comment' => 'IP地址'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'User-Agent'])]
    private ?string $userAgent = null;

    #[ORM\Column(options: ['comment' => '是否可疑行为', 'default' => false])]
    private bool $isSuspicious = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '可疑原因'])]
    private ?string $suspiciousReason = null;

    #[IndexColumn]
    #[Groups(['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

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

    public function getBehaviorType(): BehaviorType
    {
        return $this->behaviorType;
    }

    public function setBehaviorType(BehaviorType $behaviorType): static
    {
        $this->behaviorType = $behaviorType;
        
        // 自动判断是否为可疑行为
        if ($behaviorType->isSuspicious()) {
            $this->isSuspicious = true;
            $this->suspiciousReason = '系统自动检测：' . $behaviorType->getLabel();
        }
        
        return $this;
    }

    public function getBehaviorData(): ?array
    {
        return $this->behaviorData;
    }

    public function setBehaviorData(?array $behaviorData): static
    {
        $this->behaviorData = $behaviorData;
        return $this;
    }

    public function getVideoTimestamp(): ?string
    {
        return $this->videoTimestamp;
    }

    public function setVideoTimestamp(?string $videoTimestamp): static
    {
        $this->videoTimestamp = $videoTimestamp;
        return $this;
    }

    public function getDeviceFingerprint(): ?string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(?string $deviceFingerprint): static
    {
        $this->deviceFingerprint = $deviceFingerprint;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function isSuspicious(): bool
    {
        return $this->isSuspicious;
    }

    public function setIsSuspicious(bool $isSuspicious): static
    {
        $this->isSuspicious = $isSuspicious;
        return $this;
    }

    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    public function setSuspiciousReason(?string $suspiciousReason): static
    {
        $this->suspiciousReason = $suspiciousReason;
        return $this;
    }

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(?\DateTimeImmutable $createTime): static
    {
        $this->createTime = $createTime;
        return $this;
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
        ]);
    }

    /**
     * 检查是否为视频控制行为
     */
    public function isVideoControl(): bool
    {
        return $this->getBehaviorCategory() === 'video_control';
    }

    /**
     * 检查是否为空闲相关行为
     */
    public function isIdleRelated(): bool
    {
        return $this->getBehaviorCategory() === 'idle_detection';
    }

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
        return sprintf('学习行为[%s] - 类型:%s 时间:%s', 
            $this->id ?? '未知',
            $this->behaviorType->getLabel(),
            $this->createTime?->format('Y-m-d H:i:s') ?? '未知'
        );
    }
} 