<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure\Learn;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoPlay;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * ReportJobTrainingCourseVideoPlay 测试
 */
class ReportJobTrainingCourseVideoPlayTest extends TestCase
{
    private LearnSessionRepository&MockObject $sessionRepository;
    private Security&MockObject $security;
    private AdapterInterface&MockObject $cache;
    private AsyncInsertService&MockObject $doctrineService;
    private ReportJobTrainingCourseVideoPlay $procedure;

    protected function setUp(): void
    {
        $this->sessionRepository = $this->createMock(LearnSessionRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->doctrineService = $this->createMock(AsyncInsertService::class);
        
        $this->procedure = new ReportJobTrainingCourseVideoPlay(
            $this->sessionRepository,
            $this->security,
            $this->cache,
            $this->doctrineService
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

    public function test_execute_returns_success_message(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($session);

        $lesson = $this->createMock(\Tourze\TrainCourseBundle\Entity\Lesson::class);
        $lesson->expects($this->once())
            ->method('getId')
            ->willReturn('lesson-123');
            
        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $this->sessionRepository->expects($this->once())
            ->method('findOtherActiveSessionsByStudent')
            ->with($user, 'lesson-123')
            ->willReturn([]);

        $session->expects($this->once())
            ->method('isFinished')
            ->willReturn(false);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('上报成功', $result['__message']);
    }
}