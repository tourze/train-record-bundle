<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
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
#[ORM\Entity(repositoryClass: FaceDetectRepository::class)]
#[ORM\Table(name: 'face_detect', options: ['comment' => '表描述'])]
#[ORM\Index(name: 'idx_session_id', columns: ['session_id'])]
#[ORM\Index(name: 'idx_create_time', columns: ['create_time'])]
class FaceDetect implements AdminArrayInterface, ApiArrayInterface, \Stringable
{
    use CreateTimeAware;
    use SnowflakeKeyAware;
    

    #[ORM\ManyToOne(targetEntity: LearnSession::class, inversedBy: 'faceDetects')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(groups: ['api', 'admin'])]
    private LearnSession $session;

#[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '字段说明'])]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $imageData = null;

#[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '字段说明'])]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $confidence = null;

#[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '字段说明'])]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $similarity = null;

#[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '字段说明'])]
    #[Groups(groups: ['api', 'admin'])]
    private ?array $detectResult = null;

#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否验证'])]
    #[Groups(groups: ['api', 'admin'])]
    private bool $isVerified = false;

#[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '字段说明'])]
    #[Groups(groups: ['api', 'admin'])]
    private ?string $errorMessage = null;


    public function getImageData(): ?string
    {
        return $this->imageData;
    }

    public function setImageData(?string $imageData): static
    {
        $this->imageData = $imageData;
        return $this;
    }

    public function getDetectResult(): ?array
    {
        return $this->detectResult;
    }

    public function setDetectResult(?array $detectResult): static
    {
        $this->detectResult = $detectResult;
        return $this;
    }

    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'session_id' => $this->getSession()->getId(),
            'confidence' => $this->getConfidence(),
            'similarity' => $this->getSimilarity(),
            'is_verified' => $this->isVerified(),
            'error_message' => $this->getErrorMessage(),
            'create_time' => $this->getCreateTime()->format('Y-m-d H:i:s'),
        ];
    }

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
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

    public function getConfidence(): ?string
    {
        return $this->confidence;
    }

    public function setConfidence(?string $confidence): static
    {
        $this->confidence = $confidence;
        return $this;
    }

    public function getSimilarity(): ?string
    {
        return $this->similarity;
    }

    public function setSimilarity(?string $similarity): static
    {
        $this->similarity = $similarity;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }


    public function retrieveApiArray(): array
    {
        return $this->toApiArray();
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'confidence' => $this->getConfidence(),
            'similarity' => $this->getSimilarity(),
            'is_verified' => $this->isVerified(),
            'create_time' => $this->getCreateTime()->format('c'),
        ];
    }
    
    public function __toString(): string
    {
        return (string) $this->id;
    }
}