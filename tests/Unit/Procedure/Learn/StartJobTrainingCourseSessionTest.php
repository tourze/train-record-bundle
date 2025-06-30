<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure\Learn;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;
use Tourze\TrainCourseBundle\Entity\Chapter;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainCourseBundle\Repository\LessonRepository;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Procedure\Learn\StartJobTrainingCourseSession;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * StartJobTrainingCourseSession 测试
 */
class StartJobTrainingCourseSessionTest extends TestCase
{
    private RegistrationRepository&MockObject $registrationRepository;
    private LessonRepository&MockObject $lessonRepository;
    private LearnSessionRepository&MockObject $sessionRepository;
    private AsyncInsertService&MockObject $doctrineService;
    private Security&MockObject $security;
    private StartJobTrainingCourseSession $procedure;

    protected function setUp(): void
    {
        $this->registrationRepository = $this->createMock(RegistrationRepository::class);
        $this->lessonRepository = $this->createMock(LessonRepository::class);
        $this->sessionRepository = $this->createMock(LearnSessionRepository::class);
        $this->doctrineService = $this->createMock(AsyncInsertService::class);
        $this->security = $this->createMock(Security::class);
        
        $this->procedure = new StartJobTrainingCourseSession(
            $this->registrationRepository,
            $this->lessonRepository,
            $this->sessionRepository,
            $this->doctrineService,
            $this->security
        );
    }

    public function test_execute_throws_exception_when_registration_not_found(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registrationId = 'reg-123';
        $lessonId = 'lesson-123';
        
        $this->procedure->registrationId = $registrationId;
        $this->procedure->lessonId = $lessonId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'id' => $registrationId,
                'student' => $user,
            ])
            ->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('找不到报名信息');

        $this->procedure->execute();
    }

    public function test_execute_throws_exception_when_course_invalid(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        
        $registrationId = 'reg-123';
        $lessonId = 'lesson-123';
        
        $this->procedure->registrationId = $registrationId;
        $this->procedure->lessonId = $lessonId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($registration);

        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('课程已下架');

        $this->procedure->execute();
    }

    public function test_execute_throws_exception_when_other_active_sessions_exist(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        $lesson = $this->createMock(Lesson::class);
        $chapter = $this->createMock(Chapter::class);
        $activeSession = $this->createMock(LearnSession::class);
        $activeCourse = $this->createMock(Course::class);
        $activeLesson = $this->createMock(Lesson::class);
        
        $registrationId = 'reg-123';
        $lessonId = 'lesson-123';
        
        $this->procedure->registrationId = $registrationId;
        $this->procedure->lessonId = $lessonId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($registration);

        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $course->expects($this->once())
            ->method('getId')
            ->willReturn('course-123');

        $this->lessonRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $lessonId])
            ->willReturn($lesson);

        $lesson->expects($this->once())
            ->method('getChapter')
            ->willReturn($chapter);

        $chapter->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);

        $this->sessionRepository->expects($this->once())
            ->method('findOtherActiveSessionsByStudent')
            ->with($user, $lessonId)
            ->willReturn([$activeSession]);

        $activeSession->expects($this->once())
            ->method('getCourse')
            ->willReturn($activeCourse);

        $activeSession->expects($this->once())
            ->method('getLesson')
            ->willReturn($activeLesson);

        $activeCourse->expects($this->once())
            ->method('getTitle')
            ->willReturn('活跃课程');

        $activeLesson->expects($this->once())
            ->method('getTitle')
            ->willReturn('活跃课时');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('您正在学习课程"活跃课程"的课时"活跃课时"，请先完成或暂停当前学习后再开始新的课程');

        $this->procedure->execute();
    }

    public function test_execute_creates_new_session_and_returns_success(): void
    {
        $user = $this->createMock(UserInterface::class);
        $registration = $this->createMock(Registration::class);
        $course = $this->createMock(Course::class);
        $lesson = $this->createMock(Lesson::class);
        $chapter = $this->createMock(Chapter::class);
        
        $registrationId = 'reg-123';
        $lessonId = 'lesson-123';
        
        $this->procedure->registrationId = $registrationId;
        $this->procedure->lessonId = $lessonId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->registrationRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($registration);

        $registration->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $course->expects($this->once())
            ->method('getId')
            ->willReturn('course-123');

        $this->lessonRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $lessonId])
            ->willReturn($lesson);

        $lesson->expects($this->once())
            ->method('getChapter')
            ->willReturn($chapter);

        $chapter->expects($this->once())
            ->method('getCourse')
            ->willReturn($course);

        $this->sessionRepository->expects($this->once())
            ->method('findOtherActiveSessionsByStudent')
            ->willReturn([]);

        $this->sessionRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->sessionRepository->expects($this->once())
            ->method('save');

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert');

        $result = $this->procedure->execute();

    }
}