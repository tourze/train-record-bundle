<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainClassroomBundle\Service\RegistrationService;
use Tourze\TrainCourseBundle\Service\LessonService;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 因为可以从报班ID中读取到课程，所以这里不需要声明课程ID
 */
#[MethodDoc(summary: '开始观看指定视频')]
#[MethodExpose(method: 'StartJobTrainingCourseSession')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
class StartJobTrainingCourseSession extends LockableProcedure
{
    #[MethodParam(description: '报班ID')]
    public string $registrationId;

    #[MethodParam(description: '课时ID')]
    public string $lessonId;

    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly LessonService $lessonService,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly DoctrineService $doctrineService,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
        $student = $this->validateUser();
        $registration = $this->validateRegistration($student);
        $course = $this->validateCourse($registration);
        $lesson = $this->validateLesson($course);
        $this->checkConcurrentLearning($student, $lesson);

        $startTime = new DateTimeImmutable();
        $learnSession = $this->findOrCreateLearnSession($student, $registration, $lesson, $startTime);

        $this->activateSession($learnSession, $course, $startTime);
        $this->logStartAction($learnSession, $student);

        return $learnSession->retrieveApiArray();
    }

    private function validateUser(): UserInterface
    {
        $student = $this->security->getUser();
        if (null === $student) {
            throw new ApiException('用户未登录');
        }

        return $student;
    }

    private function validateRegistration(UserInterface $student): Registration
    {
        $registration = $this->registrationService->findById($this->registrationId);
        if (null === $registration) {
            throw new ApiException('找不到报名信息');
        }

        if ($registration->getStudent() !== $student) {
            throw new ApiException('找不到报名信息');
        }

        return $registration;
    }

    private function validateCourse(Registration $registration): Course
    {
        $course = $registration->getCourse();
        if (false === $course->isValid()) {
            throw new ApiException('课程已下架');
        }

        return $course;
    }

    private function validateLesson(Course $course): Lesson
    {
        $lesson = $this->lessonService->findById($this->lessonId);
        if (null === $lesson) {
            throw new ApiException('找不到课时信息[1]');
        }

        if ($lesson->getChapter()->getCourse()->getId() !== $course->getId()) {
            throw new ApiException('找不到课时信息[2]');
        }

        return $lesson;
    }

    private function checkConcurrentLearning(UserInterface $student, Lesson $lesson): void
    {
        $otherActiveSessions = $this->sessionRepository->findOtherActiveSessionsByStudent($student, $this->lessonId);

        if ([] !== $otherActiveSessions && isset($otherActiveSessions[0])) {
            $activeSession = $otherActiveSessions[0];
            $courseName = $activeSession->getCourse()->getTitle();
            $lessonName = $activeSession->getLesson()->getTitle();

            throw new ApiException(sprintf('您正在学习课程"%s"的课时"%s"，请先完成或暂停当前学习后再开始新的课程', $courseName, $lessonName), -886);
        }
    }

    private function findOrCreateLearnSession(UserInterface $student, Registration $registration, Lesson $lesson, DateTimeImmutable $startTime): LearnSession
    {
        $learnSession = $this->sessionRepository->findOneBy([
            'student' => $student,
            'registration' => $registration,
            'lesson' => $lesson,
        ]);

        if (null === $learnSession) {
            $this->validateNoUnfinishedSession($student, $registration);
            $learnSession = $this->createNewSession($student, $registration, $lesson, $startTime);
        }

        return $learnSession;
    }

    private function validateNoUnfinishedSession(UserInterface $student, Registration $registration): void
    {
        $otherSession = $this->sessionRepository->findOneBy([
            'student' => $student,
            'registration' => $registration,
            'finished' => false,
        ]);

        if (null !== $otherSession) {
            throw new ApiException('请先完成上一课时');
        }
    }

    private function createNewSession(UserInterface $student, Registration $registration, Lesson $lesson, DateTimeImmutable $startTime): LearnSession
    {
        $learnSession = new LearnSession();
        $learnSession->setStudent($student);
        $learnSession->setRegistration($registration);
        $learnSession->setLesson($lesson);
        $learnSession->setFirstLearnTime($startTime);
        $learnSession->setLastLearnTime($startTime);

        return $learnSession;
    }

    private function activateSession(LearnSession $learnSession, Course $course, DateTimeImmutable $startTime): void
    {
        $learnSession->setActive(true);
        $learnSession->setCourse($course);
        $learnSession->setLastLearnTime($startTime);

        $this->entityManager->persist($learnSession);
        $this->entityManager->flush();
    }

    private function logStartAction(LearnSession $learnSession, UserInterface $student): void
    {
        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::START);

        $this->doctrineService->asyncInsert($log);
    }
}
