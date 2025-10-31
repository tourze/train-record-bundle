<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[AsCommand(
    name: self::NAME,
    description: '清理无效的学习会话（3分钟内未更新的活跃会话）',
)]
#[AsCronTask(
    expression: '* * * * *'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnSessionCleanupCommand extends Command
{
    protected const NAME = 'train:learn-session:cleanup';
    private const INACTIVE_THRESHOLD_MINUTES = 3;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '模拟运行，只显示将被清理的会话，不实际执行'
            )
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_REQUIRED,
                '无效阈值（分钟）',
                self::INACTIVE_THRESHOLD_MINUTES
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $thresholdOption = $input->getOption('threshold');
        $threshold = is_numeric($thresholdOption) ? (int) $thresholdOption : self::INACTIVE_THRESHOLD_MINUTES;

        $this->displayIntroduction($io, $dryRun, $threshold);

        try {
            $inactiveSessions = $this->findInactiveSessions($threshold);

            if ([] === $inactiveSessions) {
                return $this->handleNoInactiveSessions($io);
            }

            $this->displayInactiveSessions($inactiveSessions, $io);

            return $this->processCleanup($inactiveSessions, $dryRun, $io);
        } catch (\Exception $e) {
            return $this->handleCleanupError($e, $io);
        }
    }

    /**
     * 显示命令介绍
     */
    private function displayIntroduction(SymfonyStyle $io, bool $dryRun, int $threshold): void
    {
        $io->title('清理无效的学习会话');

        if ($dryRun) {
            $io->note('模拟运行模式：不会实际修改数据');
        }

        $io->info(sprintf('查找 %d 分钟内未更新的活跃学习会话...', $threshold));
    }

    /**
     * 查找无效会话
     * @return array<LearnSession>
     */
    private function findInactiveSessions(int $threshold): array
    {
        return $this->sessionRepository->findInactiveActiveSessions($threshold);
    }

    /**
     * 处理无无效会话的情况
     */
    private function handleNoInactiveSessions(SymfonyStyle $io): int
    {
        $io->success('没有发现无效的学习会话');

        return Command::SUCCESS;
    }

    /**
     * 显示无效会话信息
     * @param array<LearnSession> $inactiveSessions
     */
    private function displayInactiveSessions(array $inactiveSessions, SymfonyStyle $io): void
    {
        $io->section(sprintf('发现 %d 个无效的学习会话', count($inactiveSessions)));

        $table = $this->buildSessionsTable($inactiveSessions);
        $io->table(
            ['会话ID', '学员', '课程', '课时', '最后更新时间', '当前进度'],
            $table
        );
    }

    /**
     * 构建会话表格数据
     * @param array<LearnSession> $inactiveSessions
     * @return array<array<string>>
     */
    private function buildSessionsTable(array $inactiveSessions): array
    {
        $table = [];
        foreach ($inactiveSessions as $session) {
            $table[] = [
                (string) $session->getId(),
                $session->getStudent()->getUserIdentifier(),
                $session->getCourse()->getTitle(),
                $session->getLesson()->getTitle(),
                $session->getLastLearnTime()?->format('Y-m-d H:i:s') ?? 'N/A',
                (string) $session->getCurrentDuration(),
            ];
        }

        return $table;
    }

    /**
     * 处理清理操作
     * @param array<LearnSession> $inactiveSessions
     */
    private function processCleanup(array $inactiveSessions, bool $dryRun, SymfonyStyle $io): int
    {
        if ($dryRun) {
            return $this->simulateCleanup($inactiveSessions, $io);
        }

        return $this->executeCleanup($inactiveSessions, $io);
    }

    /**
     * 模拟清理
     * @param array<LearnSession> $inactiveSessions
     */
    private function simulateCleanup(array $inactiveSessions, SymfonyStyle $io): int
    {
        $io->warning(sprintf('模拟运行：将清理 %d 个无效的学习会话', count($inactiveSessions)));

        return Command::SUCCESS;
    }

    /**
     * 执行清理
     * @param array<LearnSession> $inactiveSessions
     */
    private function executeCleanup(array $inactiveSessions, SymfonyStyle $io): int
    {
        $io->section('开始清理无效会话');
        $cleanedCount = $this->cleanupSessions($inactiveSessions, $io);
        $this->entityManager->flush();
        $io->success(sprintf('成功清理 %d 个无效的学习会话', $cleanedCount));

        return Command::SUCCESS;
    }

    /**
     * 清理会话
     * @param array<LearnSession> $inactiveSessions
     */
    private function cleanupSessions(array $inactiveSessions, SymfonyStyle $io): int
    {
        $cleanedCount = 0;

        foreach ($inactiveSessions as $session) {
            if ($this->cleanupSession($session, $io)) {
                ++$cleanedCount;
            }
        }

        return $cleanedCount;
    }

    /**
     * 清理单个会话
     */
    private function cleanupSession(LearnSession $session, SymfonyStyle $io): bool
    {
        try {
            $session->setActive(false);
            $this->entityManager->persist($session);
            $this->logSessionCleanup($session);

            return true;
        } catch (\Exception $e) {
            $this->handleSessionError($session, $e, $io);

            return false;
        }
    }

    /**
     * 记录会话清理
     */
    private function logSessionCleanup(LearnSession $session): void
    {
        $lastLearnTime = $session->getLastLearnTime();
        $this->logger->info('清理无效学习会话', [
            'session_id' => $session->getId(),
            'student_id' => $session->getStudent()->getUserIdentifier(),
            'lesson_id' => $session->getLesson()->getId(),
            'last_update' => $lastLearnTime?->format('Y-m-d H:i:s') ?? 'N/A',
        ]);
    }

    /**
     * 处理会话错误
     */
    private function handleSessionError(LearnSession $session, \Exception $e, SymfonyStyle $io): void
    {
        $io->error(sprintf('清理会话 %s 失败: %s', $session->getId(), $e->getMessage()));
        $this->logger->error('清理学习会话失败', [
            'session_id' => $session->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 处理清理错误
     */
    private function handleCleanupError(\Exception $e, SymfonyStyle $io): int
    {
        $io->error('清理过程中发生错误: ' . $e->getMessage());
        $this->logger->error('学习会话清理命令执行失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return Command::FAILURE;
    }
}
