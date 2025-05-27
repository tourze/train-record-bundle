<?php

namespace SenboTrainingBundle\Procedure\Learn;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use ExamBundle\Entity\Answer;
use ExamBundle\Entity\ExamSession;
use ExamBundle\Repository\ExamSessionRepository;
use ExamBundle\Repository\PaperRepository;
use Psr\Log\LoggerInterface;
use SenboTrainingBundle\Entity\LearnLog;
use SenboTrainingBundle\Enum\LearnAction;
use SenboTrainingBundle\Repository\LearnSessionRepository;
use SenboTrainingBundle\Repository\StudentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;

#[MethodDoc('学习后提交习题答案')]
#[MethodExpose('SubmitJobTrainingPracticeResult')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class SubmitJobTrainingPracticeResult extends LockableProcedure
{
    #[MethodParam('学习会话ID')]
    public string $learnSessionId;

    #[MethodParam('试题ID')]
    public string $paperId;

    #[MethodParam('答案信息，一个对象，题目ID是key，选项ID是值')]
    public array $answers;

    #[MethodParam('开始时间')]
    public string $startTime;

    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly PaperRepository $paperRepository,
        private readonly ExamSessionRepository $examSessionRepository,
        private readonly LearnSessionRepository $learnSessionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineService $doctrineService,
        private readonly Security $security,
        private readonly LoggerInterface $procedureLogger,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findStudent($this->security->getUser());

        $learnSession = null;
        if ($this->learnSessionId) {
            $learnSession = $this->learnSessionRepository->findOneBy([
                'student' => $student,
                'id' => $this->learnSessionId,
            ]);
            if (!$learnSession) {
                throw new ApiException('找不到学习会话');
            }
        }

        if ($learnSession) {
            $paper = $learnSession->getLesson()->getPaper();
        } else {
            $paper = $this->paperRepository->findOneBy([
                'id' => $this->paperId,
            ]);
        }
        if (!$paper) {
            throw new ApiException('找不到题目');
        }

        $now = Carbon::now();

        // 这里要创建一个 session
        $examSession = new ExamSession();
        $examSession->setUser($this->security->getUser());
        $examSession->setPaper($paper);
        $examSession->setStartTime(Carbon::parse($this->startTime));
        $examSession->setEndTime($now);
        $examSession->setPass(false);
        $this->examSessionRepository->save($examSession);

        // 记录答案
        $correctCount = 0;
        foreach ($paper->getSubjects() as $subject) {
            if (!isset($this->answers[$subject->getId()])) {
                $this->procedureLogger->warning('找不到指定题目的答案', [
                    'subject' => $subject,
                    'answers' => $this->answers,
                ]);
                throw new ApiException('答案不完整，请重试');
            }

            $options = [];
            foreach ($subject->getOptions() as $_option) {
                if (in_array($_option->getId(), $this->answers[$subject->getId()])) {
                    $options[] = $_option;
                }
            }
            if (empty($options)) {
                throw new ApiException('选项不完整，请重试');
            }

            $isCorrect = true;
            $text = [];
            foreach ($options as $option) {
                $text[] = $option->getContent();
                if (!$option->isCorrect()) {
                    $isCorrect = false;
                }
            }
            $text = implode(' | ', $text);

            $answer = new Answer();
            $answer->setSession($examSession);
            $answer->setPaper($paper);
            $answer->setSubject($subject);
            $answer->setQuestionTitle($subject->getTitle());
            $answer->overrideOptions($options);
            $answer->setAnswerContent($text);
            $answer->setAnswerTime($now);
            $answer->setCorrect($isCorrect);
            $this->entityManager->persist($answer);
            if ($answer->isCorrect()) {
                ++$correctCount;
            }
        }
        $this->entityManager->flush();

        $log = new LearnLog();
        $log->setLearnSession($learnSession);
        $log->setStudent($student);
        $log->setRegistration($learnSession->getRegistration());
        $log->setLesson($learnSession->getLesson());
        $log->setAction(LearnAction::WATCH);
        $this->doctrineService->asyncInsert($log);

        // 有设置了，我们才去判断是否通过
        if ($paper->getPassQuestionCount() > 0 && $correctCount >= $paper->getPassQuestionCount()) {
            $examSession->setPass(true);
            $this->paperRepository->save($paper);

            $registration = $learnSession?->getRegistration();

            return [
                'registration' => $registration?->retrieveApiArray(),
                '__message' => '测试通过',
            ];
        }

        throw new ApiException('测试不通过');
    }
}
