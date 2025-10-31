<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserAgentBundle\Attribute\CreateUserAgentColumn;
use Tourze\DoctrineUserAgentBundle\Attribute\UpdateUserAgentColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Chapter;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Exception\UnsupportedOperatingSystemException;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习会话
 *
 * 每次开始学习，就是一次会话，然后我们要监控是否作弊之类的行为，就是监控他单次会话内的行为
 *
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: LearnSessionRepository::class)]
#[ORM\Table(name: 'job_training_learn_session', options: ['comment' => '学习记录'])]
#[ORM\UniqueConstraint(name: 'job_training_learn_session_idx_uniq', columns: ['registration_id', 'lesson_id'])]
class LearnSession implements ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;
    use IpTraceableAware;

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
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $firstLearnTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后学习时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $lastLearnTime = null;

    #[ORM\Column(options: ['comment' => '是否已完成', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $finished = false;

    #[ORM\Column(options: ['comment' => '是否激活', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $active = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '观看时间点'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Current duration must be a valid decimal')]
    private string $currentDuration = '0.00';

    #[ORM\ManyToOne(targetEntity: LearnDevice::class, inversedBy: 'learnSessions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?LearnDevice $device = null;

    /**
     * @var Collection<int, FaceDetect>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: FaceDetect::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $faceDetects;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '总时长'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Total duration must be a valid decimal')]
    private string $totalDuration = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => '有效时长'])]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,4})?$/', message: 'Effective duration must be a valid decimal')]
    private string $effectiveDuration = '0.00';

    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '会话ID'])]
    #[Assert\Length(max: 128)]
    private ?string $sessionId = null;

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

    #[CreateUserAgentColumn]
    #[Assert\Length(max: 65535)]
    private ?string $createdFromUa = null;

    #[UpdateUserAgentColumn]
    #[Assert\Length(max: 65535)]
    private ?string $updatedFromUa = null;

    public function __construct()
    {
        $this->faceDetects = new ArrayCollection();
        $this->learnLogs = new ArrayCollection();
        $this->learnBehaviors = new ArrayCollection();
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

    public function setStudent(UserInterface $student): void
    {
        $this->student = $student;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function setLesson(Lesson $lesson): void
    {
        $this->lesson = $lesson;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    public function getFirstLearnTime(): ?\DateTimeImmutable
    {
        return $this->firstLearnTime;
    }

    public function setFirstLearnTime(?\DateTimeImmutable $firstLearnTime): void
    {
        $this->firstLearnTime = $firstLearnTime;
        if (isset($this->registration) && null === $this->registration->getFirstLearnTime()) {
            $this->registration->setFirstLearnTime($firstLearnTime);
        }
    }

    public function getLastLearnTime(): ?\DateTimeImmutable
    {
        return $this->lastLearnTime;
    }

    public function setLastLearnTime(?\DateTimeImmutable $lastLearnTime): void
    {
        $this->lastLearnTime = $lastLearnTime;
        if (isset($this->registration)) {
            $this->registration->setLastLearnTime($lastLearnTime);
        }
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): void
    {
        $this->finished = $finished;
    }

    public function getFinished(): bool
    {
        return $this->finished;
    }

    public function getFinishTime(): ?\DateTimeImmutable
    {
        return $this->finishTime;
    }

    public function setFinishTime(?\DateTimeImmutable $finishTime): void
    {
        $this->finishTime = $finishTime;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getRegistration(): Registration
    {
        return $this->registration;
    }

    public function setRegistration(Registration $registration): void
    {
        $this->registration = $registration;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setCurrentDuration(string $currentDuration): void
    {
        $this->currentDuration = $currentDuration;
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

    public function setTotalDuration(string|float $totalDuration): void
    {
        $this->totalDuration = (string) $totalDuration;
    }

    public function getEffectiveDuration(): string
    {
        return $this->effectiveDuration;
    }

    public function setEffectiveDuration(string|float $effectiveDuration): void
    {
        $this->effectiveDuration = (string) $effectiveDuration;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setDevice(?LearnDevice $device): void
    {
        $this->device = $device;
    }

    public function __toString(): string
    {
        return sprintf(
            '学习会话[%s] - 学生:%s 课程:%s',
            $this->id ?? '未知',
            $this->getStudent()->getUserIdentifier(),
            $this->lesson->getTitle()
        );
    }
}
