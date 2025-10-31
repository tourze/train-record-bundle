<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
/**
 * @implements AdminArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnDeviceRepository::class)]
#[ORM\Table(name: 'job_training_learn_device', options: ['comment' => '学习设备管理'])]
#[ORM\UniqueConstraint(name: 'uniq_device_fingerprint', columns: ['device_fingerprint'])]
#[ORM\Index(name: 'job_training_learn_device_idx_user_device', columns: ['user_id', 'is_active'])]
class LearnDevice implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $userId;

    #[ORM\Column(length: 128, nullable: false, options: ['comment' => '设备指纹'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $deviceFingerprint;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '设备名称'])]
    #[Assert\Length(max: 100)]
    private ?string $deviceName = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '设备类型（PC/Mobile/Tablet）'])]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['PC', 'Mobile', 'Tablet', null], message: 'Invalid device type')]
    private ?string $deviceType = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '操作系统'])]
    #[Assert\Length(max: 100)]
    private ?string $operatingSystem = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '浏览器信息'])]
    #[Assert\Length(max: 100)]
    private ?string $browser = null;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '屏幕分辨率'])]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^\d+x\d+$/', message: 'Screen resolution must be in format: 1920x1080', match: false)]
    private ?string $screenResolution = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '时区'])]
    #[Assert\Length(max: 50)]
    private ?string $timezone = null;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '语言设置'])]
    #[Assert\Length(max: 20)]
    private ?string $language = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '设备特征JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $deviceFeatures = null;

    #[ORM\Column(options: ['comment' => '是否激活', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    #[ORM\Column(options: ['comment' => '是否可信设备', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isTrusted = false;

    #[ORM\Column(options: ['comment' => '是否被阻止', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isBlocked = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '阻止原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $blockReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '首次使用时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $firstUseTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后使用时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $lastUseTime = null;

    #[ORM\Column(options: ['comment' => '使用次数', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $usageCount = 0;

    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '最后使用的IP地址'])]
    #[Assert\Length(max: 45)]
    private ?string $lastIpAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '最后User-Agent'])]
    #[Assert\Length(max: 65535)]
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

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getDeviceFingerprint(): string
    {
        return $this->deviceFingerprint;
    }

    public function setDeviceFingerprint(string $deviceFingerprint): void
    {
        $this->deviceFingerprint = $deviceFingerprint;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): void
    {
        $this->deviceName = $deviceName;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(?string $operatingSystem): void
    {
        $this->operatingSystem = $operatingSystem;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): void
    {
        $this->browser = $browser;
    }

    public function getScreenResolution(): ?string
    {
        return $this->screenResolution;
    }

    public function setScreenResolution(?string $screenResolution): void
    {
        $this->screenResolution = $screenResolution;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDeviceFeatures(): ?array
    {
        return $this->deviceFeatures;
    }

    /**
     * @param array<string, mixed>|null $deviceFeatures
     */
    public function setDeviceFeatures(?array $deviceFeatures): void
    {
        $this->deviceFeatures = $deviceFeatures;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isTrusted(): bool
    {
        return $this->isTrusted;
    }

    public function setIsTrusted(bool $isTrusted): void
    {
        $this->isTrusted = $isTrusted;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): void
    {
        $this->isBlocked = $isBlocked;
    }

    public function getBlockReason(): ?string
    {
        return $this->blockReason;
    }

    public function setBlockReason(?string $blockReason): void
    {
        $this->blockReason = $blockReason;
    }

    public function getFirstUseTime(): ?\DateTimeImmutable
    {
        return $this->firstUseTime;
    }

    public function setFirstUseTime(?\DateTimeImmutable $firstUseTime): void
    {
        $this->firstUseTime = $firstUseTime;
    }

    public function getLastUseTime(): ?\DateTimeImmutable
    {
        return $this->lastUseTime;
    }

    public function setLastUseTime(?\DateTimeImmutable $lastUseTime): void
    {
        $this->lastUseTime = $lastUseTime;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): void
    {
        $this->usageCount = $usageCount;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    public function setLastIpAddress(?string $lastIpAddress): void
    {
        $this->lastIpAddress = $lastIpAddress;
    }

    public function getLastUserAgent(): ?string
    {
        return $this->lastUserAgent;
    }

    public function setLastUserAgent(?string $lastUserAgent): void
    {
        $this->lastUserAgent = $lastUserAgent;
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

        if (null === $this->firstUseTime) {
            $this->firstUseTime = $now;
        }

        $this->lastUseTime = $now;
        ++$this->usageCount;

        if (null !== $ipAddress) {
            $this->lastIpAddress = $ipAddress;
        }

        if (null !== $userAgent) {
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

        if (null !== $this->deviceName) {
            $parts[] = $this->deviceName;
        }

        if (null !== $this->operatingSystem) {
            $parts[] = $this->operatingSystem;
        }

        if (null !== $this->browser) {
            $parts[] = $this->browser;
        }

        $result = implode(' / ', $parts);

        return '' !== $result ? $result : '未知设备';
    }

    public function setDeviceInfo(string $deviceInfo): void
    {
        // Note: This is a convenience method for testing
        // In real usage, device info is computed from other fields
        $parts = explode(' / ', $deviceInfo);

        // 安全访问数组元素，确保键存在
        $this->deviceName = $parts[0] ?? '';
        $this->operatingSystem = $parts[1] ?? '';
        $this->browser = $parts[2] ?? '';
    }

    public function getBrowserInfo(): ?string
    {
        return $this->browser;
    }

    public function getOsInfo(): ?string
    {
        return $this->operatingSystem;
    }

    /**
     * @return array<string, mixed>
     */
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
            'firstUseTime' => $this->firstUseTime?->format('Y-m-d H:i:s'),
            'lastUseTime' => $this->lastUseTime?->format('Y-m-d H:i:s'),
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
            'firstUseTime' => $this->firstUseTime?->format('Y-m-d H:i:s'),
            'lastUseTime' => $this->lastUseTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '学习设备[%s] - %s %s',
            $this->id ?? '未知',
            $this->deviceName ?? '未命名设备',
            $this->isActive ? '(活跃)' : '(未激活)'
        );
    }
}
