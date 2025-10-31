<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Procedure;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainClassroomBundle\Service\RegistrationService;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc(summary: '获取学员的学习明细')]
#[MethodExpose(method: 'GetJobTrainingLearnSessionDetail')]
#[MethodTag(name: '培训记录')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
class GetJobTrainingLearnSessionDetail extends BaseProcedure
{
    #[MethodParam(description: '报班ID')]
    public string $registrationId;

    public function __construct(
        private readonly Security $security,
        private readonly RegistrationService $registrationService,
        private readonly LearnSessionRepository $learnSessionRepository,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();

        $registration = $this->registrationService->findById($this->registrationId);

        // 验证该报名是否属于当前用户
        if (null === $registration || $registration->getStudent() !== $student) {
            throw new ApiException('找不到对应的报名信息');
        }

        $list = [];
        $sessions = $this->learnSessionRepository->findBy(['registration' => $registration]);
        foreach ($sessions as $session) {
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
