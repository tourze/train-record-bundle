<?php

namespace Tourze\TrainRecordBundle\Procedure;

use BizUserBundle\Repository\BizUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;

#[MethodDoc('获取学员的学习记录')]
#[MethodExpose('GetJobTrainingLearnRecordList')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GetJobTrainingLearnRecordList extends BaseProcedure
{
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

        $registrations = $this->registrationRepository->findBy(['student' => $student]);
        $list = [];
        foreach ($registrations as $registration) {
            $tmp = $registration->retrieveApiArray();
            // $tmp['certificate'] = $registration->getCertificate()?->retrieveApiArray();
            $list[] = $tmp;
        }

        return [
            'list' => $list,
        ];
    }
}
