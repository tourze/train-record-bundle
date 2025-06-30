<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Procedure\Learn;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoTimeUpdate;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * ReportJobTrainingCourseVideoTimeUpdate 测试
 */
class ReportJobTrainingCourseVideoTimeUpdateTest extends TestCase
{
    private LearnSessionRepository&MockObject $sessionRepository;
    private Security&MockObject $security;
    private FaceDetectRepository&MockObject $faceDetectRepository;
    private AdapterInterface&MockObject $cache;
    private AsyncInsertService&MockObject $doctrineService;
    private EntityManagerInterface&MockObject $entityManager;
    private ReportJobTrainingCourseVideoTimeUpdate $procedure;

    protected function setUp(): void
    {
        $this->sessionRepository = $this->createMock(LearnSessionRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->faceDetectRepository = $this->createMock(FaceDetectRepository::class);
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->doctrineService = $this->createMock(AsyncInsertService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->procedure = new ReportJobTrainingCourseVideoTimeUpdate(
            $this->sessionRepository,
            $this->security,
            $this->faceDetectRepository,
            $this->cache,
            $this->doctrineService,
            $this->entityManager
        );
    }

    public function test_execute_throws_exception_when_session_not_found(): void
    {
        $user = $this->createMock(UserInterface::class);
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;
        $this->procedure->currentTime = '100';
        $this->procedure->duration = '300';

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

    public function test_execute_throws_exception_when_progress_too_fast(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $lesson = $this->createMock(Lesson::class);
        
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;
        $this->procedure->currentTime = '150';
        $this->procedure->duration = '300';

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('user-123');

        $session->expects($this->any())
            ->method('getId')
            ->willReturn($sessionId);

        $session->expects($this->once())
            ->method('getCurrentDuration')
            ->willReturn('100'); // 当前进度150 - 上次进度100 = 50 > 30

        $session->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($session);

        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($sessionId);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($session);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('环境异常，请重新学习');

        $this->procedure->execute();
    }

    public function test_execute_returns_success_message(): void
    {
        $user = $this->createMock(UserInterface::class);
        $session = $this->createMock(LearnSession::class);
        $lesson = $this->createMock(Lesson::class);
        $registration = $this->createMock(\Tourze\TrainClassroomBundle\Entity\Registration::class);
        
        $sessionId = 'session-123';
        $this->procedure->sessionId = $sessionId;
        $this->procedure->currentTime = '120';
        $this->procedure->duration = '300';

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('user-123');

        $session->expects($this->any())
            ->method('getId')
            ->willReturn($sessionId);

        $session->expects($this->once())
            ->method('getCurrentDuration')
            ->willReturn('100'); // 当前进度120 - 上次进度100 = 20 < 30

        $session->expects($this->any())
            ->method('getLesson')
            ->willReturn($lesson);

        $session->expects($this->once())
            ->method('getRegistration')
            ->willReturn($registration);

        $lesson->expects($this->once())
            ->method('getFaceDetectDuration')
            ->willReturn(0); // 不需要人脸识别

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($session);

        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($sessionId);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $session->expects($this->once())
            ->method('setLastLearnTime');

        $session->expects($this->once())
            ->method('setCurrentDuration')
            ->with('120');

        $session->expects($this->once())
            ->method('setTotalDuration')
            ->with('300');

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session);

        $this->doctrineService->expects($this->once())
            ->method('asyncInsert');

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('上报成功', $result['__message']);
    }
}