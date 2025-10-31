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
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc(summary: '上报视频观看进度')]
#[MethodExpose(method: 'ReportJobTrainingCourseVideoTimeUpdate')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
class ReportJobTrainingCourseVideoTimeUpdate extends BaseProcedure
{
    #[MethodParam(description: '学习会话ID')]
    public string $sessionId;

    #[MethodParam(description: '当前进度')]
    public string $currentTime;

    #[MethodParam(description: '总时长')]
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

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $student = $this->security->getUser();
        if (null === $student) {
            throw new ApiException('用户未认证');
        }
        $learnSession = $this->validateAndGetSession($student);

        $this->validateLearningSession($student, $learnSession);
        $this->validateProgressChange($learnSession);
        $this->updateSessionProgress($learnSession);
        $this->createLearnLog($learnSession, $student);

        return $this->checkFaceDetectionRequired($learnSession) ?? ['__message' => '上报成功'];
    }

    private function validateAndGetSession(UserInterface $student): LearnSession
    {
        $learnSession = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);

        if (null === $learnSession) {
            throw new ApiException('找不到学习记录');
        }

        return $learnSession;
    }

    private function validateLearningSession(UserInterface $student, LearnSession $learnSession): void
    {
        $cache = $this->cache->getItem("student_learning_{$student->getUserIdentifier()}");

        if ($cache->isHit()) {
            $learningSessionId = $cache->get();
            if ($learningSessionId !== $learnSession->getId()) {
                $this->removeSessionAndThrow($learnSession, '学习异常，请检查环境');
            }
        }
    }

    private function validateProgressChange(LearnSession $learnSession): void
    {
        $currentDuration = $learnSession->getCurrentDuration();
        $progressDiff = (float) $this->currentTime - (is_numeric($currentDuration) ? (float) $currentDuration : 0.0);

        if ($progressDiff > 30) {
            $this->removeSessionAndThrow($learnSession, '环境异常，请重新学习', -652);
        }
    }

    private function removeSessionAndThrow(LearnSession $learnSession, string $message, int $code = 0): void
    {
        $this->entityManager->remove($learnSession);
        $this->entityManager->flush();
        throw new ApiException($message, $code);
    }

    private function updateSessionProgress(LearnSession $learnSession): void
    {
        $learnSession->setLastLearnTime(CarbonImmutable::now());
        $learnSession->setCurrentDuration($this->currentTime);
        $learnSession->setTotalDuration($this->duration);

        $this->entityManager->persist($learnSession);
        $this->entityManager->flush();
    }

    private function createLearnLog(LearnSession $learnSession, UserInterface $student): void
    {
        $log = new LearnLog();

        // 设置学习会话
        $log->setLearnSession($learnSession);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());

        // 设置学生
        $log->setStudent($student);

        $log->setAction(LearnAction::WATCH);
        $this->doctrineService->asyncInsert($log);
    }

    /**
     * @return ?array<string, mixed>
     */
    private function checkFaceDetectionRequired(mixed $learnSession): ?array
    {
        if (!is_object($learnSession) || !method_exists($learnSession, 'getLesson') || !method_exists($learnSession, 'getId')) {
            return null;
        }

        $lesson = $learnSession->getLesson();
        if (!is_object($lesson) || !method_exists($lesson, 'getFaceDetectDuration')) {
            return null;
        }

        $faceDetectDuration = $lesson->getFaceDetectDuration();
        if (!is_numeric($faceDetectDuration) || (float) $faceDetectDuration <= 0) {
            return null;
        }

        if ($this->isFaceDetectionNeeded($learnSession)) {
            return [
                'sessionId' => $learnSession->getId(),
                'nextAction' => 'face-detect',
            ];
        }

        return null;
    }

    private function isFaceDetectionNeeded(mixed $learnSession): bool
    {
        if (!is_object($learnSession) || !method_exists($learnSession, 'getStudent') || !method_exists($learnSession, 'getLesson') || !method_exists($learnSession, 'getCurrentDuration')) {
            return false;
        }

        $student = $learnSession->getStudent();
        $lesson = $learnSession->getLesson();

        if (!is_object($lesson) || !method_exists($lesson, 'getFaceDetectDuration')) {
            return false;
        }

        $lastFaceDetect = $this->faceDetectRepository->findOneBy([
            'student' => $student,
            'lesson' => $lesson,
            'pass' => true,
        ], ['id' => 'DESC']);

        if (null !== $lastFaceDetect) {
            return $this->isLastDetectionExpired($lastFaceDetect, $lesson);
        }

        $currentDuration = $learnSession->getCurrentDuration();
        $faceDetectDuration = $lesson->getFaceDetectDuration();

        return is_numeric($currentDuration) && is_numeric($faceDetectDuration) && (float) $currentDuration >= (float) $faceDetectDuration;
    }

    private function isLastDetectionExpired(mixed $lastFaceDetect, mixed $lesson): bool
    {
        if (!is_object($lastFaceDetect) || !method_exists($lastFaceDetect, 'getCreateTime')) {
            return true;
        }

        if (!is_object($lesson) || !method_exists($lesson, 'getFaceDetectDuration')) {
            return true;
        }

        $createTime = $lastFaceDetect->getCreateTime();
        if (!$createTime instanceof \DateTimeInterface) {
            return true;
        }
        $timeDiff = CarbonImmutable::now()->diffInSeconds($createTime);
        $faceDetectDuration = $lesson->getFaceDetectDuration();

        return is_numeric($faceDetectDuration) ? $timeDiff > (float) $faceDetectDuration : true;
    }
}
