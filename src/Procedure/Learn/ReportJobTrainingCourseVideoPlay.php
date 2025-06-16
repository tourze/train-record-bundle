<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Carbon\Carbon;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

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
        #[Autowire(service: 'cache.app')] private readonly AdapterInterface $cache,
        private readonly DoctrineService $doctrineService,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();

        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if (!$learnSession) {
            throw new ApiException('找不到学习记录');
        }

        // 检查是否有其他活跃的学习会话
        $otherActiveSessions = $this->sessionRepository->findOtherActiveSessionsByStudent($student, $learnSession->getLesson()->getId());
        if (!empty($otherActiveSessions)) {
            $activeSession = $otherActiveSessions[0];
            $courseName = $activeSession->getCourse()->getName();
            $lessonName = $activeSession->getLesson()->getTitle();
            
            throw new ApiException(
                sprintf(
                    '您正在学习课程"%s"的课时"%s"，请先完成或暂停当前学习后再开始新的课程',
                    $courseName,
                    $lessonName
                ),
                -886
            );
        }

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间
            $learnSession->setLastLearnTime(Carbon::now());
            // 将会话设置为活跃状态
            $learnSession->setActive(true);
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
        $cache = $this->cache->getItem("student_learning_{$student->getId()}");
        $cache->set($learnSession->getId());
        $cache->expiresAfter(60 * 60 * 24);
        $this->cache->save($cache);

        return [
            '__message' => '上报成功',
        ];
    }
}
