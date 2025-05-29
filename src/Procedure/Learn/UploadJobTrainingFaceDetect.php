<?php

namespace Tourze\TrainRecordBundle\Procedure\Learn;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Entity\FaceDetect;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Repository\StudentRepository;
use Tourze\TrainRecordBundle\Service\BaiduFaceService;

#[MethodDoc('人脸识别上传')]
#[MethodExpose('UploadJobTrainingFaceDetect')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class UploadJobTrainingFaceDetect extends LockableProcedure
{
    #[MethodParam('学习会话ID')]
    public string $sessionId;

    #[MethodParam('截图URL')]
    public string $photoUrl;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly StudentRepository $studentRepository,
        private readonly BaiduFaceService $faceService,
        private readonly FaceDetectRepository $faceDetectRepository,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findStudent($this->security->getUser());
        if (!$student) {
            throw new ApiException('请先绑定学员信息', -885);
        }

        $session = $this->sessionRepository->findOneBy([
            'id' => $this->sessionId,
            'student' => $student,
        ]);
        if (!$session) {
            throw new ApiException('找不到学习记录');
        }

        $img1 = trim($student->getFaceInitPhoto() ?: $student->getWhiteCertPhoto());
        $img2 = trim($this->photoUrl);
        if ($img1 === $img2) {
            throw new ApiException('图片数据不正确');
        }

        $log = new FaceDetect();
        $log->setStudent($student);
        $log->setSession($session);
        $log->setRegistration($session->getRegistration());
        $log->setCourse($session->getCourse());
        $log->setLesson($session->getLesson());
        $log->setCaptureImage($img2);
        $log->setKeySecond($session->getCurrentDuration());

        // 一个后门
        if ('ignore' === $img2) {
            $log->setDiffScore(80);
            $log->setPass(true);
        } else {
            try {
                $log->setDiffScore($this->faceService->match($img1, $img2));
            } catch (\Throwable $exception) {
                $this->procedureLogger->error('百度人脸对别时发生异常', [
                    'img1' => $img1,
                    'img2' => $img2,
                    'exception' => $exception,
                ]);
                throw new ApiException('对比失败，请注意不要遮挡脸部', previous: $exception);
            }
            $log->setPass($log->getDiffScore() >= 80); // 80分才算合格
        }

        $this->faceDetectRepository->save($log);

        if (!$log->isPass()) {
            throw new ApiException('识别不通过');
        }

        return [
            '__showToast' => '识别通过',
        ];
    }
}
