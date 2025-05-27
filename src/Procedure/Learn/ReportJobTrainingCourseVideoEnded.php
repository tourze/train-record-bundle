<?php

namespace SenboTrainingBundle\Procedure\Learn;

use Carbon\Carbon;
use ExamBundle\Repository\ExamSessionRepository;
use Psr\SimpleCache\CacheInterface;
use SenboTrainingBundle\Entity\LearnLog;
use SenboTrainingBundle\Enum\LearnAction;
use SenboTrainingBundle\Enum\RegistrationLearnStatus;
use SenboTrainingBundle\Repository\LearnSessionRepository;
use SenboTrainingBundle\Repository\RegistrationRepository;
use SenboTrainingBundle\Repository\StudentRepository;
use SenboTrainingBundle\Service\TestPaperService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

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
        private readonly StudentRepository $studentRepository,
        private readonly TestPaperService $testPaperService,
        private readonly CacheInterface $cache,
        private readonly RegistrationRepository $registrationRepository,
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
            $this->sessionRepository->save($learnSession);

            // 判断是否已经完成所有课程
            if (RegistrationLearnStatus::FINISHED === $registration->getLearnStatus()) {
                $registration->setFinished(true);
                $registration->setFinishTime(Carbon::now());
                $this->registrationRepository->save($registration);
            }
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
                    // 这里直接返回题目算了
                    'questions' => $this->testPaperService->generateQuestionList($lesson->getPaper(), $this->security->getUser()),
                ];
            }
        }

        return [
            'registration' => $registration->retrieveApiArray(),
            'message' => '上报成功',
        ];
    }
}
