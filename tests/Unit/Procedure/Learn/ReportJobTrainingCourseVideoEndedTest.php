<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure\Learn;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoEnded;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * ReportJobTrainingCourseVideoEnded 测试
 */
class ReportJobTrainingCourseVideoEndedTest extends TestCase
{
    private LearnSessionRepository&MockObject $sessionRepository;
    private AdapterInterface&MockObject $cache;
    private AsyncInsertService&MockObject $doctrineService;
    private Security&MockObject $security;
    private ReportJobTrainingCourseVideoEnded $procedure;

    protected function setUp(): void
    {
        $this->sessionRepository = $this->createMock(LearnSessionRepository::class);
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->doctrineService = $this->createMock(AsyncInsertService::class);
        $this->security = $this->createMock(Security::class);
        
        $this->procedure = new ReportJobTrainingCourseVideoEnded(
            $this->sessionRepository,
            $this->cache,
            $this->doctrineService,
            $this->security
        );
    }

    public function test_execute_throws_exception_when_user_not_authenticated(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户类型错误');

        $this->procedure->execute();
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

    public function test_execute_completes_unfinished_session(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $registration = $this->createMock(Registration::class);
        $lesson = $this->createMock(Lesson::class);
        // CacheItem 是 final 类，不能被 mock，这里不需要验证缓存操作的具体细节
        
        $sessionId = 'session-123';
        $userIdentifier = 'user-123';
        $totalDuration = 3600;
        
        $this->procedure->sessionId = $sessionId;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($userIdentifier);

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
            ->method('getRegistration')
            ->willReturn($registration);

        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $session->expects($this->once())
            ->method('isFinished')
            ->willReturn(false);

        $session->expects($this->once())
            ->method('getTotalDuration')
            ->willReturn($totalDuration);

        $session->expects($this->once())
            ->method('setLastLearnTime')
            ->with($this->isInstanceOf(CarbonImmutable::class));

        $session->expects($this->once())
            ->method('setFinished')
            ->with(true);

        $session->expects($this->once())
            ->method('setCurrentDuration')
            ->with($totalDuration);

        $session->expects($this->once())
            ->method('setActive')
            ->with(false);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session);

        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with("student_learning_{$userIdentifier}");

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->callback(function ($log) use ($session, $user, $registration, $lesson) {
                return $log instanceof LearnLog &&
                       $log->getLearnSession() === $session &&
                       $log->getStudent() === $user &&
                       $log->getRegistration() === $registration &&
                       $log->getLesson() === $lesson &&
                       $log->getAction() === LearnAction::ENDED;
            }));

        $registrationData = ['id' => 'reg-123', 'status' => 'active'];
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('registration', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($registrationData, $result['registration']);
        $this->assertEquals('上报成功', $result['message']);
    }

    public function test_execute_handles_already_finished_session(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $registration = $this->createMock(Registration::class);
        $lesson = $this->createMock(Lesson::class);
        
        $sessionId = 'session-123';
        $userIdentifier = 'user-123';
        
        $this->procedure->sessionId = $sessionId;

        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn($userIdentifier);

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
            ->method('getRegistration')
            ->willReturn($registration);

        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $session->expects($this->once())
            ->method('isFinished')
            ->willReturn(true);

        // 对于已完成的会话，不应该调用 setFinished 等方法
        $session->expects($this->never())
            ->method('setFinished');

        $this->sessionRepository->expects($this->never())
            ->method('save');

        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with("student_learning_{$userIdentifier}");

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert')
            ->with($this->isInstanceOf(LearnLog::class));

        $registrationData = ['id' => 'reg-123'];
        $registration->expects($this->once())
            ->method('retrieveApiArray')
            ->willReturn($registrationData);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('registration', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('上报成功', $result['message']);
    }
}