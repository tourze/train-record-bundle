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
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;

/**
 * 学习档案管理实体
 * 
 * 管理学习记录的归档，满足3年保存期限要求。
 * 支持多种归档格式、完整性验证、自动过期清理等功能。
 */
#[ORM\Entity(repositoryClass: LearnArchiveRepository::class)]
#[ORM\Table(name: 'job_training_learn_archive', options: ['comment' => '学习档案管理'])]
#[ORM\UniqueConstraint(name: 'uniq_user_course', columns: ['user_id', 'course_id'])]
#[ORM\Index(name: 'idx_archive_status', columns: ['archive_status'])]
#[ORM\Index(name: 'idx_expiry_date', columns: ['expiry_date'])]
#[ORM\Index(name: 'idx_archive_date', columns: ['archive_date'])]
class LearnArchive implements ApiArrayInterface, AdminArrayInterface
{
    use TimestampableAware;
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '用户ID'])]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '会话汇总JSON'])]
    private ?array $sessionSummary = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行为汇总JSON'])]
    private ?array $behaviorSummary = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '异常汇总JSON'])]
    private ?array $anomalySummary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => '总有效学习时长（秒）', 'default' => '0.0000'])]
    private string $totalEffectiveTime = '0.0000';

    #[ORM\Column(options: ['comment' => '总会话数', 'default' => 0])]
    private int $totalSessions = 0;

    #[IndexColumn]
    #[ORM\Column(length: 20, enumType: ArchiveStatus::class, options: ['comment' => '档案状态', 'default' => 'active'])]
    private ArchiveStatus $archiveStatus = ArchiveStatus::ACTIVE;

    #[ORM\Column(length: 10, enumType: ArchiveFormat::class, options: ['comment' => '归档格式', 'default' => 'json'])]
    private ArchiveFormat $archiveFormat = ArchiveFormat::JSON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '归档日期'])]
    private ?\DateTimeImmutable $archiveDate = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '过期日期（3年后）'])]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '归档文件路径'])]
    private ?string $archivePath = null;

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '归档文件哈希（完整性验证）'])]
    private ?string $archiveHash = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '文件大小（字节）'])]
    private ?string $fileSize = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '压缩比率'])]
    private ?string $compressionRatio = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '归档元数据JSON'])]
    private ?array $archiveMetadata = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证结果JSON'])]
    private ?array $verificationResult = null;

    private ?\DateTimeImmutable $lastVerificationTime = null;


    public function __construct()
    {
        $this->archiveDate = new \DateTimeImmutable();
        $this->expiryDate = new \DateTimeImmutable('+3 years'); // 3年保存期限
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function getSessionSummary(): ?array
    {
        return $this->sessionSummary;
    }

    public function setSessionSummary(?array $sessionSummary): static
    {
        $this->sessionSummary = $sessionSummary;
        return $this;
    }

    public function getBehaviorSummary(): ?array
    {
        return $this->behaviorSummary;
    }

    public function setBehaviorSummary(?array $behaviorSummary): static
    {
        $this->behaviorSummary = $behaviorSummary;
        return $this;
    }

    public function getAnomalySummary(): ?array
    {
        return $this->anomalySummary;
    }

    public function setAnomalySummary(?array $anomalySummary): static
    {
        $this->anomalySummary = $anomalySummary;
        return $this;
    }

    public function getTotalEffectiveTime(): float
    {
        return (float) $this->totalEffectiveTime;
    }

    public function setTotalEffectiveTime(float $totalEffectiveTime): static
    {
        $this->totalEffectiveTime = (string) max(0, $totalEffectiveTime);
        return $this;
    }

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function setTotalSessions(int $totalSessions): static
    {
        $this->totalSessions = max(0, $totalSessions);
        return $this;
    }

    public function getArchiveStatus(): ArchiveStatus
    {
        return $this->archiveStatus;
    }

    public function setArchiveStatus(ArchiveStatus $archiveStatus): static
    {
        $this->archiveStatus = $archiveStatus;
        return $this;
    }

    public function getArchiveFormat(): ArchiveFormat
    {
        return $this->archiveFormat;
    }

    public function setArchiveFormat(ArchiveFormat $archiveFormat): static
    {
        $this->archiveFormat = $archiveFormat;
        return $this;
    }

    public function getArchiveDate(): ?\DateTimeImmutable
    {
        return $this->archiveDate;
    }

    public function setArchiveDate(?\DateTimeImmutable $archiveDate): static
    {
        $this->archiveDate = $archiveDate;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeImmutable $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getArchivePath(): ?string
    {
        return $this->archivePath;
    }

    public function setArchivePath(?string $archivePath): static
    {
        $this->archivePath = $archivePath;
        return $this;
    }

    public function getArchiveHash(): ?string
    {
        return $this->archiveHash;
    }

    public function setArchiveHash(?string $archiveHash): static
    {
        $this->archiveHash = $archiveHash;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize ? (int) $this->fileSize : null;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize !== null ? (string) max(0, $fileSize) : null;
        return $this;
    }

    public function getCompressionRatio(): ?float
    {
        return $this->compressionRatio ? (float) $this->compressionRatio : null;
    }

    public function setCompressionRatio(?float $compressionRatio): static
    {
        $this->compressionRatio = $compressionRatio !== null ? (string) max(0, min(100, $compressionRatio)) : null;
        return $this;
    }

    public function getArchiveMetadata(): ?array
    {
        return $this->archiveMetadata;
    }

    public function setArchiveMetadata(?array $archiveMetadata): static
    {
        $this->archiveMetadata = $archiveMetadata;
        return $this;
    }

    public function getVerificationResult(): ?array
    {
        return $this->verificationResult;
    }

    public function setVerificationResult(?array $verificationResult): static
    {
        $this->verificationResult = $verificationResult;
        return $this;
    }

    public function getLastVerificationTime(): ?\DateTimeImmutable
    {
        return $this->lastVerificationTime;
    }

    public function setLastVerificationTime(?\DateTimeImmutable $lastVerificationTime): static
    {
        $this->lastVerificationTime = $lastVerificationTime;
        return $this;
    }/**
     * 标记为已归档
     */
    public function markAsArchived(string $archivePath, string $archiveHash): static
    {
        $this->setArchiveStatus(ArchiveStatus::ARCHIVED);
        $this->setArchivePath($archivePath);
        $this->setArchiveHash($archiveHash);
        $this->setArchiveDate(new \DateTimeImmutable());
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
        if (!$this->archivePath || !$this->archiveHash) {
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
            'verifiedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->setLastVerificationTime(new \DateTimeImmutable());

        return $isValid;
    }

    /**
     * 检查是否已过期
     */
    public function isExpired(): bool
    {
        if (!$this->expiryDate) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expiryDate;
    }

    /**
     * 检查是否需要验证
     */
    public function needsVerification(): bool
    {
        if (!$this->lastVerificationTime) {
            return true;
        }

        // 每月验证一次
        $nextVerificationTime = \DateTime::createFromInterface($this->lastVerificationTime);
        $nextVerificationTime->add(new \DateInterval('P1M'));

        return new \DateTimeImmutable() > $nextVerificationTime;
    }

    /**
     * 获取剩余保存天数
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->expiryDate) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if ($now > $this->expiryDate) {
            return 0;
        }

        return $now->diff($this->expiryDate)->days;
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedFileSize(): string
    {
        $size = $this->getFileSize();
        if ($size === null) {
            return '未知';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * 生成档案摘要
     */
    public function generateSummary(): array
    {
        return [
            'userId' => $this->userId,
            'courseId' => $this->course->getId(),
            'courseTitle' => $this->course->getTitle(),
            'totalSessions' => $this->totalSessions,
            'totalEffectiveTime' => $this->getTotalEffectiveTime(),
            'archiveDate' => $this->archiveDate?->format('Y-m-d H:i:s'),
            'expiryDate' => $this->expiryDate?->format('Y-m-d H:i:s'),
            'remainingDays' => $this->getRemainingDays(),
            'fileSize' => $this->getFormattedFileSize(),
            'compressionRatio' => $this->getCompressionRatio(),
            'isExpired' => $this->isExpired(),
            'needsVerification' => $this->needsVerification(),
        ];
    }

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
            'archiveDate' => $this->archiveDate?->format('Y-m-d H:i:s'),
            'expiryDate' => $this->expiryDate?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

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
            'archiveDate' => $this->archiveDate?->format('Y-m-d H:i:s'),
            'expiryDate' => $this->expiryDate?->format('Y-m-d H:i:s'),
            'lastVerificationTime' => $this->lastVerificationTime?->format('Y-m-d H:i:s'),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
} 