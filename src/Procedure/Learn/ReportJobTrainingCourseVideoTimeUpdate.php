<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc('上报视频观看进度')]
#[MethodExpose('ReportJobTrainingCourseVideoTimeUpdate')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ReportJobTrainingCourseVideoTimeUpdate extends BaseProcedure
{
    #[MethodParam('学习会话ID')]
    public string $sessionId;

    #[MethodParam('当前进度')]
    public string $currentTime;

    #[MethodParam('总时长')]
    public string $duration;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly Security $security,
        private readonly FaceDetectRepository $faceDetectRepository,
        #[Autowire(service: 'cache.app')] private readonly AdapterInterface $cache,
        private readonly DoctrineService $doctrineService,
        private readonly EntityManagerInterface $entityManager,
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

        // 检查学习是否合法
        $cache = $this->cache->getItem("student_learning_{$student->getUserIdentifier()}");
        if ($cache->isHit()) {
            $learningSessionId = $cache->get();
            if ($learningSessionId !== $learnSession->getId()) {
                $this->entityManager->remove($learnSession);
                $this->entityManager->flush();
                throw new ApiException('学习异常，请检查环境');
            }
        }

        $lesson = $learnSession->getLesson();

        // 上次的进度比今次大，是允许的
        // 今次的进度比上次大太多就不允许咯
        if (((float)$this->currentTime - (float)$learnSession->getCurrentDuration()) > 30) {
            $this->entityManager->remove($learnSession);
            $this->entityManager->flush();
            throw new ApiException('环境异常，请重新学习', -652);
        }

        // 记录最后学习时间和观看位置
        $learnSession->setLastLearnTime(CarbonImmutable::now());
        $learnSession->setCurrentDuration($this->currentTime);
        $learnSession->setTotalDuration($this->duration);
        $this->sessionRepository->save($learnSession);

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::WATCH);
        $this->doctrineService->asyncInsert($log);

        // 需要人脸识别
        if ($lesson->getFaceDetectDuration() > 0) {
            $needFace = false;
            $lastFaceDetect = $this->faceDetectRepository->findOneBy([
                'student' => $student,
                'lesson' => $lesson,
                'pass' => true,
            ], ['id' => 'DESC']);
            if ($lastFaceDetect !== null) {
                if ((bool) CarbonImmutable::now()->diffInSeconds($lastFaceDetect->getCreateTime()) > $lesson->getFaceDetectDuration()) {
                    $needFace = true;
                }
            } else {
                if ($learnSession->getCurrentDuration() >= $lesson->getFaceDetectDuration()) {
                    $needFace = true;
                }
            }

            if ($needFace) {
                return [
                    'sessionId' => $learnSession->getId(),
                    'nextAction' => 'face-detect', // 告诉前端需要人脸识别
                ];
            }
        }

        return [
            '__message' => '上报成功',
        ];
    }
}
