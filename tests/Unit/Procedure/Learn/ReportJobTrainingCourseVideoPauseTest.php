<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure\Learn;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoPause;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * ReportJobTrainingCourseVideoPause 测试
 */
class ReportJobTrainingCourseVideoPauseTest extends TestCase
{
    private LearnSessionRepository&MockObject $sessionRepository;
    private AsyncInsertService&MockObject $doctrineService;
    private Security&MockObject $security;
    private ReportJobTrainingCourseVideoPause $procedure;

    protected function setUp(): void
    {
        $this->sessionRepository = $this->createMock(LearnSessionRepository::class);
        $this->doctrineService = $this->createMock(AsyncInsertService::class);
        $this->security = $this->createMock(Security::class);
        
        $this->procedure = new ReportJobTrainingCourseVideoPause(
            $this->sessionRepository,
            $this->doctrineService,
            $this->security
        );
    }

    public function test_execute_throws_exception_when_session_not_found(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'id' => $sessionId,
                'student' => $user,
            ])
            ->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('找不到学习记录');

        $this->procedure->execute();
    }

    public function test_execute_pauses_unfinished_session(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $registration = $this->createMock(Registration::class);
        $lesson = $this->createMock(Lesson::class);
        
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'id' => $sessionId,
                'student' => $user,
            ])
            ->willReturn($session);

        $session->expects($this->once())
            ->method('isFinished')
            ->willReturn(false);

        $session->expects($this->once())
            ->method('setLastLearnTime')
            ->with($this->isInstanceOf(CarbonImmutable::class));

        $session->expects($this->once())
            ->method('setActive')
            ->with(false);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session);

        $session->expects($this->once())
            ->method('getRegistration')
            ->willReturn($registration);

        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->callback(function ($log) use ($session, $user, $registration, $lesson) {
                return $log instanceof LearnLog &&
                       $log->getLearnSession() === $session &&
                       $log->getStudent() === $user &&
                       $log->getRegistration() === $registration &&
                       $log->getLesson() === $lesson &&
                       $log->getAction() === LearnAction::PAUSE;
            }));

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('上报成功', $result['__message']);
    }

    public function test_execute_handles_already_finished_session(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $registration = $this->createMock(Registration::class);
        $lesson = $this->createMock(Lesson::class);
        
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'id' => $sessionId,
                'student' => $user,
            ])
            ->willReturn($session);

        $session->expects($this->once())
            ->method('isFinished')
            ->willReturn(true);

        // 对于已完成的会话，不应该更新状态
        $session->expects($this->never())
            ->method('setLastLearnTime');

        $session->expects($this->never())
            ->method('setActive');

        $this->sessionRepository->expects($this->never())
            ->method('save');

        $session->expects($this->once())
            ->method('getRegistration')
            ->willReturn($registration);

        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->isInstanceOf(LearnLog::class));

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('上报成功', $result['__message']);
    }
}