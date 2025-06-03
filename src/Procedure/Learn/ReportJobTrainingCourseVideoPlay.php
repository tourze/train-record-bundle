<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Carbon\Carbon;
use Psr\SimpleCache\CacheInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Repository\StudentRepository;

#[MethodDoc('开始观看指定视频')]
#[MethodExpose('ReportJobTrainingCourseVideoPlay')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class ReportJobTrainingCourseVideoPlay extends BaseProcedure
{
    #[MethodParam('学习会话ID')]
    public string $sessionId;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly Security $security,
        private readonly StudentRepository $studentRepository,
        private readonly CacheInterface $cache,
        private readonly DoctrineService $doctrineService,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findStudent($this->security->getUser());
        if (!$student) {
            throw new ApiException('请先绑定学员信息', -885);
        }

        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if (!$learnSession) {
            throw new ApiException('找不到学习记录');
        }

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间
            $learnSession->setLastLearnTime(Carbon::now());
            $this->sessionRepository->save($learnSession);
        }

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::PLAY);
        $this->doctrineService->asyncInsert($log);

        // 当一个学员开始播放视频了，我们就标记他正在学习
        $this->cache->set("student_learning_{$student->getId()}", $learnSession->getId(), 60 * 60 * 24);

        return [
            '__message' => '上报成功',
        ];
    }
}
