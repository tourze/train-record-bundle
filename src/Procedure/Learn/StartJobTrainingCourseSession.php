<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use BizUserBundle\Repository\BizUserRepository;
use Carbon\Carbon;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService as DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;
use Tourze\TrainCourseBundle\Repository\LessonRepository;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 因为可以从报班ID中读取到课程，所以这里不需要声明课程ID
 */
#[MethodDoc('开始观看指定视频')]
#[MethodExpose('StartJobTrainingCourseSession')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class StartJobTrainingCourseSession extends LockableProcedure
{
    #[MethodParam('报班ID')]
    public string $registrationId;

    #[MethodParam('课时ID')]
    public string $lessonId;

    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly LessonRepository $lessonRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly BizUserRepository $studentRepository,
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

        // 报班
        $registration = $this->registrationRepository->findOneBy([
            'id' => $this->registrationId,
            'student' => $student,
        ]);
        if (!$registration) {
            throw new ApiException('找不到报名信息');
        }

        // 课程
        $course = $registration->getCourse();
        if (!$course->isValid()) {
            throw new ApiException('课程已下架');
        }

        // 课时
        $lesson = $this->lessonRepository->findOneBy([
            'id' => $this->lessonId,
        ]);
        if (!$lesson) {
            throw new ApiException('找不到课时信息[1]');
        }
        if ($lesson->getChapter()->getCourse()->getId() !== $course->getId()) {
            throw new ApiException('找不到课时信息[2]');
        }

        $startTime = Carbon::now();

        // 检查是否有其他活跃的学习会话（跨课程检查）
        $otherActiveSessions = $this->sessionRepository->findOtherActiveSessionsByStudent($student, $this->lessonId);
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

        // 查找和创建学习记录
        $learnSession = $this->sessionRepository->findOneBy([
            'student' => $student,
            'registration' => $registration,
            'lesson' => $lesson,
        ]);
        if (!$learnSession) {
            // 如果有其他还没完成的学习会话，那我们不能继续
            $otherSession = $this->sessionRepository->findOneBy([
                'student' => $student,
                'registration' => $registration,
                'finished' => false,
            ]);
            if ($otherSession) {
                throw new ApiException('请先完成上一课时');
            }

            $learnSession = new LearnSession();
            $learnSession->setStudent($student);
            $learnSession->setRegistration($registration);
            $learnSession->setLesson($lesson);
            $learnSession->setFirstLearnTime($startTime);
            $learnSession->setLastLearnTime($startTime);
        }
        
        // 设置会话为活跃状态
        $learnSession->setActive(true);
        $learnSession->setSupplier($registration->getSupplier());
        $learnSession->setCourse($course);
        $learnSession->setLastLearnTime($startTime);
        $this->sessionRepository->save($learnSession);

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::START);
        $this->doctrineService->asyncInsert($log);

        return $learnSession->retrieveApiArray();
    }
}
