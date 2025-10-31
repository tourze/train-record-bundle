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
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;

/**
 * 学习档案管理实体
 *
 * 管理学习记录的归档，满足3年保存期限要求。
 * 支持多种归档格式、完整性验证、自动过期清理等功能。
 */
/**
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnArchiveRepository::class)]
#[ORM\Table(name: 'job_training_learn_archive', options: ['comment' => '学习档案管理'])]
#[ORM\UniqueConstraint(name: 'uniq_user_course', columns: ['user_id', 'course_id'])]
class LearnArchive implements ApiArrayInterface, AdminArrayInterface, \Stringable
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

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '会话汇总JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $sessionSummary = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为汇总JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $behaviorSummary = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常汇总JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $anomalySummary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '总有效学习时长（秒）', 'default' => '0.0000'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Total effective time must be a valid decimal')]
    private string $totalEffectiveTime = '0.0000';

    #[ORM\Column(options: ['comment' => '总会话数', 'default' => 0])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $totalSessions = 0;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: ArchiveStatus::class, options: ['comment' => '档案状态', 'default' => 'active'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ArchiveStatus::class, 'cases'])]
    private ArchiveStatus $archiveStatus = ArchiveStatus::ACTIVE;

    #[ORM\Column(length: 10, enumType: ArchiveFormat::class, options: ['comment' => '归档格式', 'default' => 'json'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ArchiveFormat::class, 'cases'])]
    private ArchiveFormat $archiveFormat = ArchiveFormat::JSON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '归档日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $archiveTime = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '过期日期（3年后）'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $expiryTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '归档文件路径'])]
    #[Assert\Length(max: 65535)]
    private ?string $archivePath = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '归档文件哈希（完整性验证）'])]
    #[Assert\Length(max: 128)]
    private ?string $archiveHash = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '文件大小（字节）'])]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'File size must be a valid integer')]
    private ?string $fileSize = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '压缩比率'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Compression ratio must be a valid decimal')]
    private ?string $compressionRatio = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '归档元数据JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $archiveMetadata = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证结果JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $verificationResult = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后验证时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $lastVerifyTime = null;

    public function __construct()
    {
        $this->archiveTime = new \DateTimeImmutable();
        $this->expiryTime = new \DateTimeImmutable('+3 years'); // 3年保存期限
    }

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

    public function getCourseId(): ?string
    {
        return $this->course->getId();
    }

    /**
     * @deprecated This method is deprecated and should not be used. Use setCourse() instead.
     */
    public function setCourseId(string $courseId): void
    {
        // This method is deprecated because it tries to assign invalid types to typed properties
        // In tests, use proper Course entities instead
        throw new UnsupportedActionException('setCourseId() is deprecated. Use setCourse() with a proper Course entity instead.');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSessionSummary(): ?array
    {
        return $this->sessionSummary;
    }

    /**
     * @param array<string, mixed>|null $sessionSummary
     */
    public function setSessionSummary(?array $sessionSummary): void
    {
        $this->sessionSummary = $sessionSummary;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBehaviorSummary(): ?array
    {
        return $this->behaviorSummary;
    }

    /**
     * @param array<string, mixed>|null $behaviorSummary
     */
    public function setBehaviorSummary(?array $behaviorSummary): void
    {
        $this->behaviorSummary = $behaviorSummary;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAnomalySummary(): ?array
    {
        return $this->anomalySummary;
    }

    /**
     * @param array<string, mixed>|null $anomalySummary
     */
    public function setAnomalySummary(?array $anomalySummary): void
    {
        $this->anomalySummary = $anomalySummary;
    }

    public function getTotalEffectiveTime(): float
    {
        return (float) $this->totalEffectiveTime;
    }

    public function setTotalEffectiveTime(float $totalEffectiveTime): void
    {
        $this->totalEffectiveTime = (string) max(0, $totalEffectiveTime);
    }

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function setTotalSessions(int $totalSessions): void
    {
        $this->totalSessions = max(0, $totalSessions);
    }

    public function getArchiveStatus(): ArchiveStatus
    {
        return $this->archiveStatus;
    }

    public function setArchiveStatus(ArchiveStatus $archiveStatus): void
    {
        $this->archiveStatus = $archiveStatus;
    }

    public function getArchiveFormat(): ArchiveFormat
    {
        return $this->archiveFormat;
    }

    public function setArchiveFormat(ArchiveFormat $archiveFormat): void
    {
        $this->archiveFormat = $archiveFormat;
    }

    public function getArchiveTime(): ?\DateTimeImmutable
    {
        return $this->archiveTime;
    }

    public function setArchiveTime(?\DateTimeImmutable $archiveTime): void
    {
        $this->archiveTime = $archiveTime;
    }

    public function getExpiryTime(): ?\DateTimeImmutable
    {
        return $this->expiryTime;
    }

    public function setExpiryTime(?\DateTimeImmutable $expiryTime): void
    {
        $this->expiryTime = $expiryTime;
    }

    public function getArchivePath(): ?string
    {
        return $this->archivePath;
    }

    public function setArchivePath(?string $archivePath): void
    {
        $this->archivePath = $archivePath;
    }

    public function getArchiveHash(): ?string
    {
        return $this->archiveHash;
    }

    public function setArchiveHash(?string $archiveHash): void
    {
        $this->archiveHash = $archiveHash;
    }

    public function getChecksum(): ?string
    {
        return $this->archiveHash;
    }

    public function setChecksum(?string $checksum): void
    {
        $this->archiveHash = $checksum;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->archiveMetadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->archiveMetadata = $metadata;
    }

    public function getFileSize(): ?int
    {
        return null !== $this->fileSize ? (int) $this->fileSize : null;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = null !== $fileSize ? (string) max(0, $fileSize) : null;
    }

    public function getCompressionRatio(): ?float
    {
        return null !== $this->compressionRatio ? (float) $this->compressionRatio : null;
    }

    public function setCompressionRatio(?float $compressionRatio): void
    {
        $this->compressionRatio = null !== $compressionRatio ? (string) max(0, min(100, $compressionRatio)) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getArchiveMetadata(): ?array
    {
        return $this->archiveMetadata;
    }

    /**
     * @param array<string, mixed>|null $archiveMetadata
     */
    public function setArchiveMetadata(?array $archiveMetadata): void
    {
        $this->archiveMetadata = $archiveMetadata;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVerificationResult(): ?array
    {
        return $this->verificationResult;
    }

    /**
     * @param array<string, mixed>|null $verificationResult
     */
    public function setVerificationResult(?array $verificationResult): void
    {
        $this->verificationResult = $verificationResult;
    }

    public function getLastVerifyTime(): ?\DateTimeImmutable
    {
        return $this->lastVerifyTime;
    }

    public function setLastVerifyTime(?\DateTimeImmutable $lastVerifyTime): void
    {
        $this->lastVerifyTime = $lastVerifyTime;
    }

    /**
     * 标记为已归档
     */
    public function markAsArchived(string $archivePath, string $archiveHash): static
    {
        $this->setArchiveStatus(ArchiveStatus::ARCHIVED);
        $this->setArchivePath($archivePath);
        $this->setArchiveHash($archiveHash);
        $this->setArchiveTime(new \DateTimeImmutable());

        return $this;
    }

    /**
     * 标记为已过期
     */
    public function markAsExpired(): static
    {
        $this->setArchiveStatus(ArchiveStatus::EXPIRED);

        return $this;
    }

    /**
     * 验证档案完整性
     */
    public function verifyIntegrity(): bool
    {
        if (null === $this->archivePath || null === $this->archiveHash) {
            return false;
        }

        if (!file_exists($this->archivePath)) {
            return false;
        }

        $currentHash = hash_file('sha256', $this->archivePath);
        $isValid = $currentHash === $this->archiveHash;

        // 记录验证结果
        $this->setVerificationResult([
            'isValid' => $isValid,
            'expectedHash' => $this->archiveHash,
            'actualHash' => $currentHash,
            'verifyTime' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->setLastVerifyTime(new \DateTimeImmutable());

        return $isValid;
    }

    /**
     * 检查是否已过期
     */
    public function isExpired(): bool
    {
        if (null === $this->expiryTime) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expiryTime;
    }

    /**
     * 检查是否需要验证
     */
    public function needsVerification(): bool
    {
        if (null === $this->lastVerifyTime) {
            return true;
        }

        // 每月验证一次
        $nextVerificationTime = \DateTime::createFromInterface($this->lastVerifyTime);
        $nextVerificationTime->add(new \DateInterval('P1M'));

        return new \DateTimeImmutable() > $nextVerificationTime;
    }

    /**
     * 获取剩余保存天数
     */
    public function getRemainingDays(): ?int
    {
        if (null === $this->expiryTime) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if ($now > $this->expiryTime) {
            return 0;
        }

        $diff = $now->diff($this->expiryTime);

        return false !== $diff->days ? $diff->days : null;
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedFileSize(): string
    {
        $size = $this->getFileSize();
        if (null === $size) {
            return '未知';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * 生成档案摘要
     *
     * @return array<string, mixed>
     */
    public function generateSummary(): array
    {
        return [
            'userId' => $this->userId,
            'courseId' => $this->course->getId(),
            'courseTitle' => $this->course->getTitle(),
            'totalSessions' => $this->totalSessions,
            'totalEffectiveTime' => $this->getTotalEffectiveTime(),
            'archiveTime' => $this->archiveTime?->format('Y-m-d H:i:s'),
            'expiryTime' => $this->expiryTime?->format('Y-m-d H:i:s'),
            'remainingDays' => $this->getRemainingDays(),
            'fileSize' => $this->getFormattedFileSize(),
            'compressionRatio' => $this->getCompressionRatio(),
            'isExpired' => $this->isExpired(),
            'needsVerification' => $this->needsVerification(),
        ];
    }

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
            'totalSessions' => $this->totalSessions,
            'totalEffectiveTime' => $this->getTotalEffectiveTime(),
            'archiveStatus' => $this->archiveStatus->value,
            'archiveStatusLabel' => $this->archiveStatus->getLabel(),
            'archiveFormat' => $this->archiveFormat->value,
            'archiveFormatLabel' => $this->archiveFormat->getLabel(),
            'fileSize' => $this->getFormattedFileSize(),
            'compressionRatio' => $this->getCompressionRatio(),
            'remainingDays' => $this->getRemainingDays(),
            'isExpired' => $this->isExpired(),
            'needsVerification' => $this->needsVerification(),
            'archiveTime' => $this->archiveTime?->format('Y-m-d H:i:s'),
            'expiryTime' => $this->expiryTime?->format('Y-m-d H:i:s'),
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
            'sessionSummary' => $this->sessionSummary,
            'behaviorSummary' => $this->behaviorSummary,
            'anomalySummary' => $this->anomalySummary,
            'totalSessions' => $this->totalSessions,
            'totalEffectiveTime' => $this->getTotalEffectiveTime(),
            'archiveStatus' => $this->archiveStatus->value,
            'archiveStatusLabel' => $this->archiveStatus->getLabel(),
            'archiveFormat' => $this->archiveFormat->value,
            'archiveFormatLabel' => $this->archiveFormat->getLabel(),
            'archivePath' => $this->archivePath,
            'archiveHash' => $this->archiveHash,
            'fileSize' => $this->getFileSize(),
            'formattedFileSize' => $this->getFormattedFileSize(),
            'compressionRatio' => $this->getCompressionRatio(),
            'archiveMetadata' => $this->archiveMetadata,
            'verificationResult' => $this->verificationResult,
            'remainingDays' => $this->getRemainingDays(),
            'isExpired' => $this->isExpired(),
            'needsVerification' => $this->needsVerification(),
            'archiveTime' => $this->archiveTime?->format('Y-m-d H:i:s'),
            'expiryTime' => $this->expiryTime?->format('Y-m-d H:i:s'),
            'lastVerifyTime' => $this->lastVerifyTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '学习档案[%s] - 用户:%s 课程:%s',
            $this->id ?? '未知',
            $this->userId ?? '未知',
            $this->course->getTitle()
        );
    }
}
