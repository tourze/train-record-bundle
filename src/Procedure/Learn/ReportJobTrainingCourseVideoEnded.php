<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc(summary: '视频观看结束')]
#[MethodExpose(method: 'ReportJobTrainingCourseVideoEnded')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
class ReportJobTrainingCourseVideoEnded extends LockableProcedure
{
    #[MethodParam(description: '学习会话ID')]
    public string $sessionId;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'cache.app')] private readonly AdapterInterface $cache,
        private readonly DoctrineService $doctrineService,
        private readonly Security $security,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();
        if (!$student instanceof UserInterface) {
            throw new ApiException('用户类型错误');
        }

        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if (null === $learnSession) {
            throw new ApiException('找不到学习记录');
        }
        $registration = $learnSession->getRegistration();

        $this->cache->deleteItem("student_learning_{$student->getUserIdentifier()}");

        $lesson = $learnSession->getLesson();

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间，同时比较为完成
            $learnSession->setLastLearnTime(CarbonImmutable::now());
            $learnSession->setFinished(true);
            $learnSession->setCurrentDuration($learnSession->getTotalDuration());
            // 将会话设置为非活跃状态
            $learnSession->setActive(false);
            $this->entityManager->persist($learnSession);
            $this->entityManager->flush();
        }

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($registration);
        $log->setLesson($lesson);
        $log->setAction(LearnAction::ENDED);
        $this->doctrineService->asyncInsert($log);

        return [
            'registration' => $registration->retrieveApiArray(),
            'message' => '上报成功',
        ];
    }
}
