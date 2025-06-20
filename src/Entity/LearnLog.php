<?php

namespace Tourze\TrainRecordBundle\Entity;

use BizUserBundle\Entity\BizUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnLogRepository;

#[ORM\Entity(repositoryClass: LearnLogRepository::class)]
#[ORM\Table(name: 'ims_job_training_learn_action_log', options: ['comment' => '学习轨迹'])]
class LearnLog implements Stringable
{
    #[Groups(['restful_read', 'api_tree', 'admin_curd', 'api_list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?LearnSession $learnSession = null;

    #[ORM\ManyToOne]
    private ?BizUser $student = null;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?Registration $registration = null;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 30, enumType: LearnAction::class, options: ['comment' => '动作'])]
    private LearnAction $action;

    #[CreateUserAgentColumn]
    private ?string $createdFromUa = null;

    #[IndexColumn]
    #[Groups(['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    #[CreatedByColumn]
    private ?string $createdBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLearnSession(): ?LearnSession
    {
        return $this->learnSession;
    }

    public function setLearnSession(?LearnSession $learnSession): static
    {
        $this->learnSession = $learnSession;

        return $this;
    }

    public function getStudent(): ?BizUser
    {
        return $this->student;
    }

    public function setStudent(?BizUser $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getRegistration(): ?Registration
    {
        return $this->registration;
    }

    public function setRegistration(?Registration $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): static
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getAction(): LearnAction
    {
        return $this->action;
    }

    public function setAction(LearnAction $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getCreatedFromUa(): ?string
    {
        return $this->createdFromUa;
    }

    public function setCreatedFromUa(?string $createdFromUa): static
    {
        $this->createdFromUa = $createdFromUa;

        return $this;
    }

    public function setCreateTime(?\DateTimeImmutable $createdAt): self
    {
        $this->createTime = $createdAt;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setCreatedFromIp(?string $createdFromIp): void
    {
        $this->createdFromIp = $createdFromIp;
    }

    public function setCreatedBy(?string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
