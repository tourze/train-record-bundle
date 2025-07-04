<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;

/**
 * 学习设备管理实体
 *
 * 管理学员的学习设备信息，支持多终端登录控制和设备识别。
 * 用于防止多设备同时学习、设备切换检测等防作弊功能。
 */
#[ORM\Entity(repositoryClass: LearnDeviceRepository::class)]
#[ORM\Table(name: 'job_training_learn_device', options: ['comment' => '学习设备管理'])]
#[ORM\UniqueConstraint(name: 'uniq_device_fingerprint', columns: ['device_fingerprint'])]
#[ORM\Index(name: 'idx_user_device', columns: ['user_id', 'is_active'])]
#[ORM\Index(name: 'idx_last_used', columns: ['last_used_time'])]
class LearnDevice implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '用户ID'])]
    private string $userId;

    #[ORM\Column(length: 128, nullable: false, options: ['comment' => '设备指纹'])]
    private string $deviceFingerprint;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '设备名称'])]
    private ?string $deviceName = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '设备类型（PC/Mobile/Tablet）'])]
    private ?string $deviceType = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '操作系统'])]
    private ?string $operatingSystem = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '浏览器信息'])]
    private ?string $browser = null;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '屏幕分辨率'])]
    private ?string $screenResolution = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '时区'])]
    private ?string $timezone = null;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '语言设置'])]
    private ?string $language = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '设备特征JSON'])]
    private ?array $deviceFeatures = null;

    #[ORM\Column(options: ['comment' => '是否激活', 'default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['comment' => '是否可信设备', 'default' => false])]
    private bool $isTrusted = false;

    #[ORM\Column(options: ['comment' => '是否被阻止', 'default' => false])]
    private bool $isBlocked = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '阻止原因'])]
    private ?string $blockReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '首次使用时间'])]
    private ?\DateTimeImmutable $firstUsedTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后使用时间'])]
    private ?\DateTimeImmutable $lastUsedTime = null;

    #[ORM\Column(options: ['comment' => '使用次数', 'default' => 0])]
    private int $usageCount = 0;

    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '最后使用的IP地址'])]
    private ?string $lastIpAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '最后User-Agent'])]
    private ?string $lastUserAgent = null;

    /**
     * @var Collection<int, LearnSession>
     */
    #[ORM\OneToMany(mappedBy: 'device', targetEntity: LearnSession::class)]
    private Collection $learnSessions;



    public function __construct()
    {
        $this->learnSessions = new ArrayCollection();
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

    public function getDeviceFingerprint(): string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(string $deviceFingerprint): static
    {
        $this->deviceFingerprint = $deviceFingerprint;
        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): static
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): static
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(?string $operatingSystem): static
    {
        $this->operatingSystem = $operatingSystem;
        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): static
    {
        $this->browser = $browser;
        return $this;
    }

    public function getScreenResolution(): ?string
    {
        return $this->screenResolution;
    }

    public function setScreenResolution(?string $screenResolution): static
    {
        $this->screenResolution = $screenResolution;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getDeviceFeatures(): ?array
    {
        return $this->deviceFeatures;
    }

    public function setDeviceFeatures(?array $deviceFeatures): static
    {
        $this->deviceFeatures = $deviceFeatures;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isTrusted(): bool
    {
        return $this->isTrusted;
    }

    public function setIsTrusted(bool $isTrusted): static
    {
        $this->isTrusted = $isTrusted;
        return $this;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    public function getBlockReason(): ?string
    {
        return $this->blockReason;
    }

    public function setBlockReason(?string $blockReason): static
    {
        $this->blockReason = $blockReason;
        return $this;
    }

    public function getFirstUsedTime(): ?\DateTimeImmutable
    {
        return $this->firstUsedTime;
    }

    public function setFirstUsedTime(?\DateTimeImmutable $firstUsedTime): static
    {
        $this->firstUsedTime = $firstUsedTime;
        return $this;
    }

    public function getLastUsedTime(): ?\DateTimeImmutable
    {
        return $this->lastUsedTime;
    }

    public function setLastUsedTime(?\DateTimeImmutable $lastUsedTime): static
    {
        $this->lastUsedTime = $lastUsedTime;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    public function setLastIpAddress(?string $lastIpAddress): static
    {
        $this->lastIpAddress = $lastIpAddress;
        return $this;
    }

    public function getLastUserAgent(): ?string
    {
        return $this->lastUserAgent;
    }

    public function setLastUserAgent(?string $lastUserAgent): static
    {
        $this->lastUserAgent = $lastUserAgent;
        return $this;
    }

    /**
     * @return Collection<int, LearnSession>
     */
    public function getLearnSessions(): Collection
    {
        return $this->learnSessions;
    }

    public function addLearnSession(LearnSession $learnSession): static
    {
        if (!$this->learnSessions->contains($learnSession)) {
            $this->learnSessions->add($learnSession);
            $learnSession->setDevice($this);
        }

        return $this;
    }

    public function removeLearnSession(LearnSession $learnSession): static
    {
        if ($this->learnSessions->removeElement($learnSession)) {
            if ($learnSession->getDevice() === $this) {
                $learnSession->setDevice(null);
            }
        }

        return $this;
    }

/**
     * 更新设备使用信息
     */
    public function updateUsage(?string $ipAddress = null, ?string $userAgent = null): static
    {
        $now = new \DateTimeImmutable();
        
        if ($this->firstUsedTime === null) {
            $this->firstUsedTime = $now;
        }
        
        $this->lastUsedTime = $now;
        $this->usageCount++;
        
        if ($ipAddress !== null) {
            $this->lastIpAddress = $ipAddress;
        }
        
        if ($userAgent !== null) {
            $this->lastUserAgent = $userAgent;
        }
        
        return $this;
    }

    /**
     * 阻止设备
     */
    public function block(string $reason): static
    {
        $this->isBlocked = true;
        $this->isActive = false;
        $this->blockReason = $reason;
        return $this;
    }

    /**
     * 解除阻止
     */
    public function unblock(): static
    {
        $this->isBlocked = false;
        $this->isActive = true;
        $this->blockReason = null;
        return $this;
    }

    /**
     * 设置为可信设备
     */
    public function trust(): static
    {
        $this->isTrusted = true;
        return $this;
    }

    /**
     * 取消可信设备
     */
    public function untrust(): static
    {
        $this->isTrusted = false;
        return $this;
    }

    /**
     * 检查设备是否可用
     */
    public function isAvailable(): bool
    {
        return $this->isActive && !$this->isBlocked;
    }

    /**
     * 获取设备简要信息
     */
    public function getDeviceInfo(): string
    {
        $parts = [];
        
        if ($this->deviceName !== null) {
            $parts[] = $this->deviceName;
        }
        
        if ($this->operatingSystem !== null) {
            $parts[] = $this->operatingSystem;
        }
        
        if ($this->browser !== null) {
            $parts[] = $this->browser;
        }
        
        $result = implode(' / ', $parts);
        return $result !== '' ? $result : '未知设备';
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'deviceFingerprint' => $this->deviceFingerprint,
            'deviceName' => $this->deviceName,
            'deviceType' => $this->deviceType,
            'deviceInfo' => $this->getDeviceInfo(),
            'isActive' => $this->isActive,
            'isTrusted' => $this->isTrusted,
            'isBlocked' => $this->isBlocked,
            'isAvailable' => $this->isAvailable(),
            'usageCount' => $this->usageCount,
            'firstUsedTime' => $this->firstUsedTime?->format('Y-m-d H:i:s'),
            'lastUsedTime' => $this->lastUsedTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'deviceFingerprint' => $this->deviceFingerprint,
            'deviceName' => $this->deviceName,
            'deviceType' => $this->deviceType,
            'operatingSystem' => $this->operatingSystem,
            'browser' => $this->browser,
            'screenResolution' => $this->screenResolution,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'deviceInfo' => $this->getDeviceInfo(),
            'isActive' => $this->isActive,
            'isTrusted' => $this->isTrusted,
            'isBlocked' => $this->isBlocked,
            'blockReason' => $this->blockReason,
            'isAvailable' => $this->isAvailable(),
            'usageCount' => $this->usageCount,
            'lastIpAddress' => $this->lastIpAddress,
            'firstUsedTime' => $this->firstUsedTime?->format('Y-m-d H:i:s'),
            'lastUsedTime' => $this->lastUsedTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf('学习设备[%s] - %s %s', 
            $this->id ?? '未知',
            $this->deviceName ?? '未命名设备',
            $this->isActive ? '(活跃)' : '(未激活)'
        );
    }
} 