<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Procedure;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainClassroomBundle\Service\RegistrationService;

#[MethodDoc(summary: '获取学员的学习记录')]
#[MethodExpose(method: 'GetJobTrainingLearnRecordList')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
class GetJobTrainingLearnRecordList extends BaseProcedure
{
    public function __construct(
        private readonly Security $security,
        private readonly RegistrationService $registrationService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $student = $this->security->getUser();
        if (null === $student) {
            throw new ApiException('用户未登录');
        }

        $registrations = $this->registrationService->findUserRegistrations($student);
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
