<?php

namespace Tourze\TrainRecordBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Service\LearnProgressService;

#[AsCommand(
    name: 'learn:data:process',
    description: '处理学习数据，计算有效学习时长'
)]
class LearnDataProcessCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnProgressService $progressService,
        private readonly LearnBehaviorService $behaviorService,
        private readonly LoggerInterface $logger,
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getOption('session-id');
        $userId = $input->getOption('user-id');
        $date = $input->getOption('date');
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = $input->getOption('dry-run');

        $io->title('学习数据处理');

        if ($dryRun) {
            $io->note('运行在试运行模式，不会实际更新数据');
        }

        try {
            $processedCount = 0;
            $errorCount = 0;

            if ($sessionId) {
                // 处理单个会话
                $processedCount = $this->processSingleSession($sessionId, $dryRun, $io);
            } elseif ($userId) {
                // 处理指定用户的会话
                $processedCount = $this->processUserSessions($userId, $date, $batchSize, $dryRun, $io);
            } else {
                // 处理指定日期的所有会话
                $processedCount = $this->processDateSessions($date, $batchSize, $dryRun, $io);
            }

            $io->success(sprintf(
                '数据处理完成！处理了 %d 个会话，错误 %d 个',
                $processedCount,
                $errorCount
            ));

            return Command::SUCCESS;

        } catch  (\Throwable $e) {
            $this->logger->error('学习数据处理失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('数据处理失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 处理单个会话
     */
    private function processSingleSession(string $sessionId, bool $dryRun, SymfonyStyle $io): int
    {
        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
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
        $startDate = new \DateTime($date . ' 00:00:00');
        $endDate = new \DateTime($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        
        $io->text(sprintf("找到用户 %s 在 %s 的 %d 个会话", $userId, $date, count($sessions)));

        return $this->processSessions($sessions, $batchSize, $dryRun, $io);
    }

    /**
     * 处理日期会话
     */
    private function processDateSessions(string $date, int $batchSize, bool $dryRun, SymfonyStyle $io): int
    {
        $startDate = new \DateTime($date . ' 00:00:00');
        $endDate = new \DateTime($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByDateRange($startDate, $endDate);
        
        $io->text(sprintf("找到 %s 的 %d 个会话", $date, count($sessions)));

        return $this->processSessions($sessions, $batchSize, $dryRun, $io);
    }

    /**
     * 批量处理会话
     */
    private function processSessions(array $sessions, int $batchSize, bool $dryRun, SymfonyStyle $io): int
    {
        $total = count($sessions);
        $processed = 0;
        $errors = 0;

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($sessions, $batchSize) as $batch) {
            foreach ($batch as $session) {
                try {
                    if (!$dryRun) {
                        // 重新计算有效学习时长
                        $this->progressService->recalculateEffectiveTime($session->getId());
                        
                        // 更新学习统计
                        $this->behaviorService->updateSessionStatistics($session->getId());
                    }
                    
                    $processed++;
                } catch  (\Throwable $e) {
                    $errors++;
                    $this->logger->error('处理会话失败', [
                        'sessionId' => $session->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $progressBar->advance();
            }

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
} 