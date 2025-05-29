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
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\UpdateUserAgentColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Exportable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习会话
 *
 * 每次开始学习，就是一次会话，然后我们要监控是否作弊之类的行为，就是监控他单次会话内的行为
 */
#[AsPermission(title: '学习记录')]
#[Deletable]
#[Exportable]
#[ORM\Entity(repositoryClass: LearnSessionRepository::class)]
#[ORM\Table(name: 'job_training_learn_session', options: ['comment' => '学习记录'])]
#[ORM\UniqueConstraint(name: 'job_training_learn_session_idx_uniq', columns: ['registration_id', 'lesson_id'])]
class
LearnSession implements ApiArrayInterface, AdminArrayInterface
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $student;

    #[ListColumn(title: '报班')]
    #[FormField(title: '报班')]
    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Registration $registration;

    #[ListColumn(title: '课程')]
    #[FormField(title: '课程')]
    #[ORM\ManyToOne]
    private Course $course;

    #[ListColumn(title: '课时')]
    #[FormField(title: '课时')]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Lesson $lesson;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '首次学习时间'])]
    private ?\DateTimeInterface $firstLearnTime = null;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '最后学习时间'])]
    private ?\DateTimeInterface $lastLearnTime = null;

    #[BoolColumn]
    #[IndexColumn]
    #[ListColumn]
    #[ORM\Column(options: ['comment' => '是否完成'])]
    private bool $finished = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    private ?\DateTimeInterface $finishTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '观看时间点'])]
    private string $currentDuration = '0.00';

    #[Ignore]
    #[ORM\OneToMany(targetEntity: FaceDetect::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $faceDetects;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true)]
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

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[CreateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '更新时IP'])]
    private ?string $updatedFromIp = null;

    #[CreateUserAgentColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '创建时UA'])]
    private ?string $createdFromUa = null;

    #[UpdateUserAgentColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '更新时UA'])]
    private ?string $updatedFromUa = null;

    #[IndexColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

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

    public function getFirstLearnTime(): ?\DateTimeInterface
    {
        return $this->firstLearnTime;
    }

    public function setFirstLearnTime(?\DateTimeInterface $firstLearnTime): static
    {
        $this->firstLearnTime = $firstLearnTime;
        if (null === $this->getRegistration()->getFirstLearnTime()) {
            $this->getRegistration()->setFirstLearnTime($firstLearnTime);
        }

        return $this;
    }

    public function getLastLearnTime(): ?\DateTimeInterface
    {
        return $this->lastLearnTime;
    }

    public function setLastLearnTime(?\DateTimeInterface $lastLearnTime): static
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

    public function getFinishTime(): ?\DateTimeInterface
    {
        return $this->finishTime;
    }

    public function setFinishTime(?\DateTimeInterface $finishTime): static
    {
        $this->finishTime = $finishTime;

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
        if ($this->faceDetects->removeElement($faceDetect)) {
            // set the owning side to null (unless already changed)
            if ($faceDetect->getSession() === $this) {
                $faceDetect->setSession(null);
            }
        }

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
        if ($this->learnBehaviors->removeElement($learnBehavior)) {
            // set the owning side to null (unless already changed)
            if ($learnBehavior->getSession() === $this) {
                $learnBehavior->setSession(null);
            }
        }

        return $this;
    }
}
