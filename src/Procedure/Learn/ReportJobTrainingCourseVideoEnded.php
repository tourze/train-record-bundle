<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use BizUserBundle\Repository\BizUserRepository;
use Carbon\Carbon;
use ExamBundle\Repository\ExamSessionRepository;
use ExamBundle\Repository\PaperRepository;
use Psr\SimpleCache\CacheInterface;
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

#[MethodDoc('视频观看结束')]
#[MethodExpose('ReportJobTrainingCourseVideoEnded')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class ReportJobTrainingCourseVideoEnded extends LockableProcedure
{
    #[MethodParam('学习会话ID')]
    public string $sessionId;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly ExamSessionRepository $examSessionRepository,
        private readonly BizUserRepository $studentRepository,
        private readonly PaperRepository $paperRepository,
        private readonly CacheInterface $cache,
        private readonly DoctrineService $doctrineService,
        private readonly Security $security,
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
        $registration = $learnSession->getRegistration();

        $this->cache->delete("student_learning_{$student->getId()}");

        $lesson = $learnSession->getLesson();

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间，同时比较为完成
            $learnSession->setLastLearnTime(Carbon::now());
            $learnSession->setFinished(true);
            $learnSession->setCurrentDuration($learnSession->getTotalDuration());
            // 将会话设置为非活跃状态
            $learnSession->setActive(false);
            $this->sessionRepository->save($learnSession);
        }

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($registration);
        $log->setLesson($lesson);
        $log->setAction(LearnAction::ENDED);
        $this->doctrineService->asyncInsert($log);

        if ($lesson->getPaper()) {
            $examSession = $this->examSessionRepository->findOneBy([
                'student' => $student,
                'paper' => $lesson->getPaper(),
                'learnSession' => $learnSession,
            ], ['id' => 'DESC']);
            if (!$examSession || !$examSession->isPass()) {
                $examSession = null;
            }

            if (!$examSession) {
                return [
                    'registration' => $registration->retrieveApiArray(),
                    'sessionId' => $learnSession->getId(),
                    'nextAction' => 'exam', // 告诉前端需要做习题了
                    'paperId' => $lesson->getPaper()->getId(),
                    // 前端需要额外调用试卷相关接口获取题目
                ];
            }
        }

        return [
            'registration' => $registration->retrieveApiArray(),
            'message' => '上报成功',
        ];
    }
}
