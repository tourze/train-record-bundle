<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc(summary: '开始观看指定视频')]
#[MethodExpose(method: 'ReportJobTrainingCourseVideoPlay')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Log]
class ReportJobTrainingCourseVideoPlay extends BaseProcedure
{
    #[MethodParam(description: '学习会话ID')]
    public string $sessionId;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly Security $security,
        #[Autowire(service: 'cache.app')] private readonly AdapterInterface $cache,
        private readonly DoctrineService $doctrineService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();
        if (null === $student) {
            throw new ApiException('用户未登录');
        }

        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if (null === $learnSession) {
            throw new ApiException('找不到学习记录');
        }

        // 检查是否有其他活跃的学习会话
        $lesson = $learnSession->getLesson();
        $lessonId = $lesson->getId();
        if (null === $lessonId) {
            throw new ApiException('课时信息无效');
        }

        $otherActiveSessions = $this->sessionRepository->findOtherActiveSessionsByStudent($student, $lessonId);
        // 安全访问数组元素，确保至少有一个活跃会话存在
        if ([] !== $otherActiveSessions && isset($otherActiveSessions[0])) {
            $activeSession = $otherActiveSessions[0];
            $courseName = $activeSession->getCourse()->getTitle();
            $lessonName = $activeSession->getLesson()->getTitle();

            throw new ApiException(sprintf('您正在学习课程"%s"的课时"%s"，请先完成或暂停当前学习后再开始新的课程', $courseName, $lessonName), -886);
        }

        if (!$learnSession->isFinished()) {
            // 记录最后学习时间
            $learnSession->setLastLearnTime(new \DateTimeImmutable());
            // 将会话设置为活跃状态
            $learnSession->setActive(true);
            $this->entityManager->persist($learnSession);
            $this->entityManager->flush();
        }

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::PLAY);
        $this->doctrineService->asyncInsert($log);

        // 当一个学员开始播放视频了，我们就标记他正在学习
        $cache = $this->cache->getItem("student_learning_{$student->getUserIdentifier()}");
        $cache->set($learnSession->getId());
        $cache->expiresAfter(60 * 60 * 24);
        $this->cache->save($cache);

        return [
            '__message' => '上报成功',
        ];
    }
}
