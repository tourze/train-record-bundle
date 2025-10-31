<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\CreatedFromIpAware;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnLogRepository;

#[ORM\Entity(repositoryClass: LearnLogRepository::class)]
#[ORM\Table(name: 'ims_job_training_learn_action_log', options: ['comment' => '学习轨迹'])]
class LearnLog implements \Stringable
{
    use CreatedByAware;
    use CreatedFromIpAware;

    #[Groups(groups: ['restful_read', 'api_tree', 'admin_curd', 'api_list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    #[Assert\Type(type: 'int')]
    private int $id = 0;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?LearnSession $learnSession = null;

    #[ORM\ManyToOne]
    private ?UserInterface $student = null;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?Registration $registration = null;

    #[ORM\ManyToOne(inversedBy: 'learnLogs')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 30, enumType: LearnAction::class, options: ['comment' => '动作'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [LearnAction::class, 'cases'])]
    private LearnAction $action;

    #[CreateUserAgentColumn]
    #[Assert\Length(max: 65535)]
    private ?string $createdFromUa = null;

    #[IndexColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '日志级别'])]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', null], message: 'Invalid log level')]
    private ?string $logLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '日志消息'])]
    #[Assert\Length(max: 65535)]
    private ?string $message = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '日志上下文JSON'])]
    #[Assert\Type(type: 'array')]
    private ?array $context = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLearnSession(): ?LearnSession
    {
        return $this->learnSession;
    }

    public function setLearnSession(?LearnSession $learnSession): void
    {
        $this->learnSession = $learnSession;
    }

    public function getStudent(): ?UserInterface
    {
        return $this->student;
    }

    public function setStudent(?UserInterface $student): void
    {
        $this->student = $student;
    }

    public function getRegistration(): ?Registration
    {
        return $this->registration;
    }

    public function setRegistration(?Registration $registration): void
    {
        $this->registration = $registration;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): void
    {
        $this->lesson = $lesson;
    }

    public function getAction(): LearnAction
    {
        return $this->action;
    }

    public function setAction(LearnAction $action): void
    {
        $this->action = $action;
    }

    public function getCreatedFromUa(): ?string
    {
        return $this->createdFromUa;
    }

    public function setCreatedFromUa(?string $createdFromUa): void
    {
        $this->createdFromUa = $createdFromUa;
    }

    public function setCreateTime(?\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function setLogLevel(?string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
