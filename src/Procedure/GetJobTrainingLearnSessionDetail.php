<?php

namespace Tourze\TrainRecordBundle\Procedure;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TrainClassroomBundle\Repository\RegistrationRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[MethodDoc('获取学员的学习明细')]
#[MethodExpose('GetJobTrainingLearnSessionDetail')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GetJobTrainingLearnSessionDetail extends BaseProcedure
{
    #[MethodParam('报班ID')]
    public string $registrationId;

    public function __construct(
        private readonly Security $security,
        private readonly RegistrationRepository $registrationRepository,
        private readonly LearnSessionRepository $learnSessionRepository,
    ) {
    }

    public function execute(): array
    {
        $student = $this->security->getUser();

        $registration = $this->registrationRepository->findOneBy([
            'student' => $student,
            'id' => $this->registrationId,
        ]);

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
