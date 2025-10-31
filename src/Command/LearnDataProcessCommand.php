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
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Service\LearnProgressService;

#[AsCommand(
    name: self::NAME,
    description: '处理学习数据，计算有效学习时长'
)]
#[AsCronTask(
    expression: '0 * * * *'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnDataProcessCommand extends Command
{
    protected const NAME = 'learn:data:process';

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnProgressService $progressService,
        private readonly LearnBehaviorService $behaviorService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'session-id',
                's',
                InputOption::VALUE_OPTIONAL,
                '指定要处理的会话ID'
            )
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定要处理的用户ID'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_OPTIONAL,
                '指定要处理的日期 (Y-m-d)',
                date('Y-m-d')
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '批处理大小',
                100
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '试运行模式，不实际更新数据'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = $this->parseOptions($input);

        $io->title('学习数据处理');

        if ($options['dryRun']) {
            $io->note('运行在试运行模式，不会实际更新数据');
        }

        try {
            $processedCount = $this->executeProcessing($options, $io);

            $io->success(sprintf(
                '数据处理完成！处理了 %d 个会话',
                $processedCount
            ));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            return $this->handleExecutionError($e, $io);
        }
    }

    /**
     * @return array{sessionId: string|null, userId: string|null, date: string, batchSize: int, dryRun: bool}
     */
    private function parseOptions(InputInterface $input): array
    {
        $sessionId = $input->getOption('session-id');
        $userId = $input->getOption('user-id');
        $date = $input->getOption('date');
        $batchSizeOption = $input->getOption('batch-size');
        $batchSize = max(1, is_numeric($batchSizeOption) ? (int) $batchSizeOption : 100);
        $dryRun = (bool) $input->getOption('dry-run');

        return [
            'sessionId' => is_string($sessionId) ? $sessionId : null,
            'userId' => is_string($userId) ? $userId : null,
            'date' => is_string($date) ? $date : date('Y-m-d'),
            'batchSize' => $batchSize,
            'dryRun' => $dryRun,
        ];
    }

    /**
     * @param array{sessionId: string|null, userId: string|null, date: string, batchSize: int, dryRun: bool} $options
     */
    private function executeProcessing(array $options, SymfonyStyle $io): int
    {
        if (null !== $options['sessionId']) {
            return $this->processSingleSession($options['sessionId'], $options['dryRun'], $io);
        }

        if (null !== $options['userId']) {
            return $this->processUserSessions(
                $options['userId'],
                $options['date'],
                $options['batchSize'],
                $options['dryRun'],
                $io
            );
        }

        return $this->processDateSessions(
            $options['date'],
            $options['batchSize'],
            $options['dryRun'],
            $io
        );
    }

    private function handleExecutionError(\Throwable $e, SymfonyStyle $io): int
    {
        $this->logger->error('学习数据处理失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $io->error('数据处理失败: ' . $e->getMessage());

        return Command::FAILURE;
    }

    /**
     * 处理单个会话
     */
    private function processSingleSession(string $sessionId, bool $dryRun, SymfonyStyle $io): int
    {
        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            $io->error("会话 {$sessionId} 不存在");

            return 0;
        }

        $io->text("处理会话: {$sessionId}");

        if (!$dryRun) {
            // 重新计算有效学习时长
            $this->progressService->recalculateEffectiveTime($sessionId);

            // 更新学习统计
            $this->behaviorService->updateSessionStatistics($sessionId);

            $this->entityManager->flush();
        }

        return 1;
    }

    /**
     * 处理用户会话
     */
    private function processUserSessions(string $userId, string $date, int $batchSize, bool $dryRun, SymfonyStyle $io): int
    {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByUserAndDateRange($userId, $startDate, $endDate);

        $io->text(sprintf('找到用户 %s 在 %s 的 %d 个会话', $userId, $date, count($sessions)));

        return $this->processSessions($sessions, max(1, $batchSize), $dryRun, $io);
    }

    /**
     * 处理日期会话
     */
    private function processDateSessions(string $date, int $batchSize, bool $dryRun, SymfonyStyle $io): int
    {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByDateRange($startDate, $endDate);

        $io->text(sprintf('找到 %s 的 %d 个会话', $date, count($sessions)));

        return $this->processSessions($sessions, max(1, $batchSize), $dryRun, $io);
    }

    /**
     * 批量处理会话
     *
     * @param array<LearnSession> $sessions
     * @param int<1, max> $batchSize
     */
    private function processSessions(array $sessions, int $batchSize, bool $dryRun, SymfonyStyle $io): int
    {
        $total = count($sessions);
        $processed = 0;
        $errors = 0;

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($sessions, $batchSize) as $batch) {
            [$batchProcessed, $batchErrors] = $this->processSessionBatch($batch, $dryRun, $progressBar);
            $processed += $batchProcessed;
            $errors += $batchErrors;

            if (!$dryRun) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        if ($errors > 0) {
            $io->warning(sprintf('处理过程中发生 %d 个错误，详情请查看日志', $errors));
        }

        return $processed;
    }

    /**
     * @param array<LearnSession> $batch
     * @param mixed $progressBar
     * @return array{int, int}
     */
    private function processSessionBatch(array $batch, bool $dryRun, $progressBar): array
    {
        $processed = 0;
        $errors = 0;

        foreach ($batch as $session) {
            $sessionId = $session->getId();
            if (null === $sessionId) {
                ++$errors;
                continue;
            }

            try {
                if (!$dryRun) {
                    // 重新计算有效学习时长
                    $this->progressService->recalculateEffectiveTime($sessionId);

                    // 更新学习统计
                    $this->behaviorService->updateSessionStatistics($sessionId);
                }

                ++$processed;
            } catch (\Throwable $e) {
                ++$errors;
                $this->logger->error('处理会话失败', [
                    'sessionId' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }

            if (is_object($progressBar) && method_exists($progressBar, 'advance')) {
                $progressBar->advance();
            }
        }

        return [$processed, $errors];
    }
}
