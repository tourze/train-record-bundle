<?php

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\UpdateUserAgentColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习会话
 *
 * 每次开始学习，就是一次会话，然后我们要监控是否作弊之类的行为，就是监控他单次会话内的行为
 */
#[ORM\Entity(repositoryClass: LearnSessionRepository::class)]
#[ORM\Table(name: 'job_training_learn_session', options: ['comment' => '学习记录'])]
#[ORM\UniqueConstraint(name: 'job_training_learn_session_idx_uniq', columns: ['registration_id', 'lesson_id'])]
class LearnSession implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $student;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Registration $registration;

    #[ORM\ManyToOne]
    private Course $course;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Lesson $lesson;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '首次学习时间'])]
    private ?\DateTimeImmutable $firstLearnTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后学习时间'])]
    private ?\DateTimeImmutable $lastLearnTime = null;

    private bool $finished = false;

    private bool $active = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '观看时间点'])]
    private string $currentDuration = '0.00';

    #[ORM\ManyToOne(targetEntity: LearnDevice::class, inversedBy: 'learnSessions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?LearnDevice $device = null;

    #[Ignore]
    #[ORM\OneToMany(targetEntity: FaceDetect::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $faceDetects;

#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '字段说明'])]
    private string $totalDuration = '0.00';

    /**
     * @var Collection<int, LearnLog>
     */
    #[ORM\OneToMany(targetEntity: LearnLog::class, mappedBy: 'learnSession')]
    private Collection $learnLogs;

    /**
     * @var Collection<int, LearnBehavior>
     */
    #[ORM\OneToMany(targetEntity: LearnBehavior::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $learnBehaviors;


    #[CreateIpColumn]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    private ?string $updatedFromIp = null;

    #[CreateUserAgentColumn]
    private ?string $createdFromUa = null;

    #[UpdateUserAgentColumn]
    private ?string $updatedFromUa = null;


    public function __construct()
    {
        $this->faceDetects = new ArrayCollection();
        $this->learnLogs = new ArrayCollection();
        $this->learnBehaviors = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setCreatedFromIp(?string $createdFromIp): self
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setUpdatedFromIp(?string $updatedFromIp): self
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
    }

    public function setCreatedFromUa(?string $createdFromUa): void
    {
        $this->createdFromUa = $createdFromUa;
    }

    public function getCreatedFromUa(): ?string
    {
        return $this->createdFromUa;
    }

    public function setUpdatedFromUa(?string $updatedFromUa): void
    {
        $this->updatedFromUa = $updatedFromUa;
    }

    public function getUpdatedFromUa(): ?string
    {
        return $this->updatedFromUa;
    }

    public function getStudent(): UserInterface
    {
        return $this->student;
    }

    public function setStudent(UserInterface $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): static
    {
        $this->lesson = $lesson;

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

    public function getFirstLearnTime(): ?\DateTimeImmutable
    {
        return $this->firstLearnTime;
    }

    public function setFirstLearnTime(?\DateTimeImmutable $firstLearnTime): static
    {
        $this->firstLearnTime = $firstLearnTime;
        if (null === $this->getRegistration()->getFirstLearnTime()) {
            $this->getRegistration()->setFirstLearnTime($firstLearnTime);
        }

        return $this;
    }

    public function getLastLearnTime(): ?\DateTimeImmutable
    {
        return $this->lastLearnTime;
    }

    public function setLastLearnTime(?\DateTimeImmutable $lastLearnTime): static
    {
        $this->lastLearnTime = $lastLearnTime;
        $this->getRegistration()->setLastLearnTime($lastLearnTime);

        return $this;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): static
    {
        $this->finished = $finished;

        return $this;
    }

    public function getFinishTime(): ?\DateTimeImmutable
    {
        return $this->finishTime;
    }

    public function setFinishTime(?\DateTimeImmutable $finishTime): static
    {
        $this->finishTime = $finishTime;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function setRegistration(Registration $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'firstLearnTime' => $this->getFirstLearnTime()?->format('Y-m-d H:i:s'),
            'lastLearnTime' => $this->getLastLearnTime()?->format('Y-m-d H:i:s'),
            'createdFromIp' => $this->getCreatedFromIp(),
            'currentDuration' => $this->getCurrentDuration(),
            'finished' => $this->isFinished(),
        ];
    }

    public function getCurrentDuration(): string
    {
        return $this->currentDuration;
    }

    public function setCurrentDuration(string $currentDuration): static
    {
        $this->currentDuration = $currentDuration;

        return $this;
    }

    /**
     * @return Collection<int, FaceDetect>
     */
    public function getFaceDetects(): Collection
    {
        return $this->faceDetects;
    }

    public function addFaceDetect(FaceDetect $faceDetect): static
    {
        if (!$this->faceDetects->contains($faceDetect)) {
            $this->faceDetects->add($faceDetect);
            $faceDetect->setSession($this);
        }

        return $this;
    }

    public function removeFaceDetect(FaceDetect $faceDetect): static
    {
        $this->faceDetects->removeElement($faceDetect);
        return $this;
    }

    public function getTotalDuration(): string
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(string $totalDuration): static
    {
        $this->totalDuration = $totalDuration;

        return $this;
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'firstLearnTime' => $this->getFirstLearnTime()?->format('Y-m-d H:i:s'),
            'lastLearnTime' => $this->getLastLearnTime()?->format('Y-m-d H:i:s'),
            'finished' => $this->isFinished(),
            'finishTime' => $this->getFinishTime()?->format('Y-m-d H:i:s'),
            'currentDuration' => $this->getCurrentDuration(),
            'totalDuration' => $this->getTotalDuration(),
        ];
    }

    /**
     * @return Collection<int, LearnLog>
     */
    public function getLearnLogs(): Collection
    {
        return $this->learnLogs;
    }

    public function addLearnLog(LearnLog $learnLog): static
    {
        if (!$this->learnLogs->contains($learnLog)) {
            $this->learnLogs->add($learnLog);
            $learnLog->setLearnSession($this);
        }

        return $this;
    }

    public function removeLearnLog(LearnLog $learnLog): static
    {
        if ($this->learnLogs->removeElement($learnLog)) {
            // set the owning side to null (unless already changed)
            if ($learnLog->getLearnSession() === $this) {
                $learnLog->setLearnSession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LearnBehavior>
     */
    public function getLearnBehaviors(): Collection
    {
        return $this->learnBehaviors;
    }

    public function addLearnBehavior(LearnBehavior $learnBehavior): static
    {
        if (!$this->learnBehaviors->contains($learnBehavior)) {
            $this->learnBehaviors->add($learnBehavior);
            $learnBehavior->setSession($this);
        }

        return $this;
    }

    public function removeLearnBehavior(LearnBehavior $learnBehavior): static
    {
        $this->learnBehaviors->removeElement($learnBehavior);
        return $this;
    }

    public function getDevice(): ?LearnDevice
    {
        return $this->device;
    }

    public function setDevice(?LearnDevice $device): static
    {
        $this->device = $device;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('学习会话[%s] - 学生:%s 课程:%s', 
            $this->id ?? '未知',
            $this->getStudent()->getUserIdentifier(),
            $this->lesson->getTitle()
        );
    }
}
