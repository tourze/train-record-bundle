<?php

namespace Tourze\TrainRecordBundle\Procedure;

use Carbon\Carbon;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\GAT2000\DocumentType;
use Tourze\IdcardManageBundle\Service\IdcardService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainRecordBundle\Repository\StudentRepository;

#[MethodDoc('提交实名认证申请')]
#[MethodExpose('SubmitJobTrainingStudentVerifyRequest')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class SubmitJobTrainingStudentVerifyRequest extends LockableProcedure
{
    #[MethodParam('姓名')]
    public string $certName;

    #[MethodParam('身份证号码')]
    public string $certNo;

    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly IdcardService $idCardService,
        private readonly Security $security,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findStudent($this->security->getUser());
        if ($student->isVerified()) {
            throw new ApiException('已认证，不能修改');
        }

        $student->setRealName($this->certName);
        $student->setIdCardNumber($this->certNo);
        $student->setIdCardType(DocumentType::ID_CARD);
        $this->studentRepository->save($student);

        if (!$this->idCardService->isValid($student->getIdCardNumber())) {
            throw new ApiException('身份证格式不正确');
        }
        if (!$this->idCardService->twoElementVerify($student->getRealName(), $student->getIdCardNumber())) {
            throw new ApiException('身份证跟姓名不一致');
        }

        $student->setVerified(true);
        $student->setVerifyTime(Carbon::now());
        $this->studentRepository->save($student);

        return [
            '__message' => '认证成功',
        ];
    }
}
