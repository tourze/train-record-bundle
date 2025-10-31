<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;

/**
 * 人脸检测记录实体
 *
 * 记录学习过程中的人脸检测结果，用于防作弊检测和学习监控。
 * 包括人脸检测置信度、检测时间、相似度评分等信息。
 */
/**
 * @implements AdminArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: FaceDetectRepository::class)]
#[ORM\Table(name: 'face_detect', options: ['comment' => '表描述'])]
#[ORM\Index(name: 'face_detect_idx_session_create', columns: ['session_id', 'create_time'])]
class FaceDetect implements AdminArrayInterface, ApiArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use CreateTimeAware;

    #[ORM\ManyToOne(targetEntity: LearnSession::class, inversedBy: 'faceDetects')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(groups: ['api', 'admin'])]
    private LearnSession $session;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 16777215)]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $imageData = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Confidence must be a valid decimal')]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $confidence = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Similarity must be a valid decimal')]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $similarity = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Type(type: 'array')]
    #[Groups(groups: ['api', 'admin'])]
    private ?array $detectResult = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否验证'])]
    #[Assert\Type(type: 'bool')]
    #[Groups(groups: ['api', 'admin'])]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 65535)]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $errorMessage = null;

    public function getImageData(): ?string
    {
        return $this->imageData;
    }

    public function setImageData(?string $imageData): void
    {
        $this->imageData = $imageData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetectResult(): ?array
    {
        return $this->detectResult;
    }

    /**
     * @param array<string, mixed>|null $detectResult
     */
    public function setDetectResult(?array $detectResult): void
    {
        $this->detectResult = $detectResult;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'session_id' => $this->getSession()->getId(),
            'confidence' => $this->getConfidence(),
            'similarity' => $this->getSimilarity(),
            'is_verified' => $this->isVerified(),
            'error_message' => $this->getErrorMessage(),
            'create_time' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getSession(): LearnSession
    {
        return $this->session;
    }

    public function setSession(LearnSession $session): void
    {
        $this->session = $session;
    }

    public function getConfidence(): ?string
    {
        return $this->confidence;
    }

    public function setConfidence(?string $confidence): void
    {
        $this->confidence = $confidence;
    }

    public function getSimilarity(): ?string
    {
        return $this->similarity;
    }

    public function setSimilarity(?string $similarity): void
    {
        $this->similarity = $similarity;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return $this->toApiArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'confidence' => $this->getConfidence(),
            'similarity' => $this->getSimilarity(),
            'is_verified' => $this->isVerified(),
            'create_time' => $this->getCreateTime()?->format('c'),
        ];
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
