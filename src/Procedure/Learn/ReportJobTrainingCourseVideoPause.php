<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Carbon\CarbonImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc('暂停视频观看')]
#[MethodExpose('ReportJobTrainingCourseVideoPause')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class ReportJobTrainingCourseVideoPause extends LockableProcedure
{
    #[MethodParam('学习会话ID')]
    public string $sessionId;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly DoctrineService $doctrineService,
        private readonly Security $security,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();

        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if ($learnSession === null) {
            throw new ApiException('找不到学习记录');
        }

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间
            $learnSession->setLastLearnTime(CarbonImmutable::now());
            // 将会话设置为非活跃状态
            $learnSession->setActive(false);
            $this->sessionRepository->save($learnSession);
        }

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::PAUSE);
        $this->doctrineService->asyncInsert($log);

        return [
            '__message' => '上报成功',
        ];
    }
}
