<?php

namespace Tourze\TrainRecordBundle\Procedure;

use BizUserBundle\Repository\BizUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;

#[MethodDoc('获取学员的学习明细')]
#[MethodExpose('GetJobTrainingLearnSessionDetail')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GetJobTrainingLearnSessionDetail extends BaseProcedure
{
    #[MethodParam('报班ID')]
    public string $registrationId;

    public function __construct(
        private readonly BizUserRepository $studentRepository,
        private readonly Security $security,
        private readonly RegistrationRepository $registrationRepository,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findStudent($this->security->getUser());
        if (!$student) {
            throw new ApiException('请先绑定学员信息', -885);
        }

        $registration = $this->registrationRepository->findOneBy([
            'student' => $student,
            'id' => $this->registrationId,
        ]);

        $list = [];
        foreach ($registration->getSessions() as $session) {
            $list[] = $session->retrieveApiArray();
        }

        // 一个班只有一个课程的，所以直接返回course算了
        return [
            ...$registration->retrieveApiArray(),
            'course' => $registration->getCourse()->retrieveApiArray(),
            'sessions' => $list,
        ];
    }
}
