<?php

namespace Tourze\TrainRecordBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnAnomalyService;

#[AsCommand(
    name: 'learn:anomaly:detect',
    description: '批量检测学习异常'
)]
class LearnAnomalyDetectCommand extends Command
{
    protected const NAME = 'learn:anomaly:detect';
    public function __construct(
                private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnAnomalyService $anomalyService,
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
                '指定要检测的会话ID'
            )
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定要检测的用户ID'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_OPTIONAL,
                '指定要检测的日期 (Y-m-d)',
                date('Y-m-d')
            )
            ->addOption(
                'anomaly-type',
                't',
                InputOption::VALUE_OPTIONAL,
                '指定异常类型 (multiple_device, rapid_progress, window_switch, idle_timeout, face_detect_fail, network_anomaly)'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '批处理大小',
                50
            )
            ->addOption(
                'auto-resolve',
                null,
                InputOption::VALUE_NONE,
                '自动解决轻微异常'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '试运行模式，不实际创建异常记录'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getOption('session-id');
        $userId = $input->getOption('user-id');
        $date = $input->getOption('date');
        $anomalyType = $input->getOption('anomaly-type');
        $batchSize = (int) $input->getOption('batch-size');
        $autoResolve = (bool) $input->getOption('auto-resolve');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('学习异常检测');

        if ((bool) $dryRun) {
            $io->note('运行在试运行模式，不会实际创建异常记录');
        }

        if ((bool) $autoResolve) {
            $io->note('启用自动解决轻微异常功能');
        }

        try {
            $detectedCount = 0;
            $resolvedCount = 0;

            if ($sessionId !== null) {
                // 检测单个会话
                $result = $this->detectSingleSession($sessionId, $anomalyType, $autoResolve, $dryRun, $io);
                $detectedCount = $result['detected'];
                $resolvedCount = $result['resolved'];
            } elseif ($userId !== null) {
                // 检测指定用户的会话
                $result = $this->detectUserSessions($userId, $date, $anomalyType, $batchSize, $autoResolve, $dryRun, $io);
                $detectedCount = $result['detected'];
                $resolvedCount = $result['resolved'];
            } else {
                // 检测指定日期的所有会话
                $result = $this->detectDateSessions($date, $anomalyType, $batchSize, $autoResolve, $dryRun, $io);
                $detectedCount = $result['detected'];
                $resolvedCount = $result['resolved'];
            }

            $io->success(sprintf(
                '异常检测完成！检测到 %d 个异常，自动解决 %d 个',
                $detectedCount,
                $resolvedCount
            ));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('学习异常检测失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('异常检测失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 检测单个会话
     */
    private function detectSingleSession(
        string $sessionId,
        ?string $anomalyType,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            $io->error("会话 {$sessionId} 不存在");
            return ['detected' => 0, 'resolved' => 0];
        }

        $io->text("检测会话: {$sessionId}");

        $detected = 0;
        $resolved = 0;

        if ($anomalyType !== null) {
            // 检测指定类型的异常
            $result = $this->detectSpecificAnomaly($sessionId, $anomalyType, $autoResolve, $dryRun);
            $detected += $result['detected'];
            $resolved += $result['resolved'];
        } else {
            // 检测所有类型的异常
            $result = $this->detectAllAnomalies($sessionId, $autoResolve, $dryRun);
            $detected += $result['detected'];
            $resolved += $result['resolved'];
        }

        return ['detected' => $detected, 'resolved' => $resolved];
    }

    /**
     * 检测用户会话
     */
    private function detectUserSessions(
        string $userId,
        string $date,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByUserAndDateRange((string) $userId, $startDate, $endDate);
        
        $io->text(sprintf("找到用户 %s 在 %s 的 %d 个会话", $userId, $date, count($sessions)));

        return $this->detectSessions($sessions, $anomalyType, $batchSize, $autoResolve, $dryRun, $io);
    }

    /**
     * 检测日期会话
     */
    private function detectDateSessions(
        string $date,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByDateRange($startDate, $endDate);
        
        $io->text(sprintf("找到 %s 的 %d 个会话", $date, count($sessions)));

        return $this->detectSessions($sessions, $anomalyType, $batchSize, $autoResolve, $dryRun, $io);
    }

    /**
     * 批量检测会话
     */
    private function detectSessions(
        array $sessions,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $total = count($sessions);
        $totalDetected = 0;
        $totalResolved = 0;

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($sessions, $batchSize) as $batch) {
            foreach ($batch as $session) {
                try {
                    if ($anomalyType !== null) {
                        $result = $this->detectSpecificAnomaly($session->getId(), $anomalyType, $autoResolve, $dryRun);
                    } else {
                        $result = $this->detectAllAnomalies($session->getId(), $autoResolve, $dryRun);
                    }
                    
                    $totalDetected += $result['detected'];
                    $totalResolved += $result['resolved'];
                } catch (\Throwable $e) {
                    $this->logger->error('检测会话异常失败', [
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

        return ['detected' => $totalDetected, 'resolved' => $totalResolved];
    }

    /**
     * 检测指定类型的异常
     */
    private function detectSpecificAnomaly(string $sessionId, string $anomalyType, bool $autoResolve, bool $dryRun): array
    {
        $detected = 0;
        $resolved = 0;

        if (!$dryRun) {
            $anomaly = null;
            
            switch ($anomalyType) {
                case 'multiple_device':
                    $session = $this->sessionRepository->find($sessionId);
                    $userId = $session->getStudent()->getId();
                    $anomalies = $this->anomalyService->detectMultipleDeviceAnomaly((string) $userId);
                    $detected = count($anomalies);
                    break;

                case 'rapid_progress':
                    // 简化实现，实际应该从行为数据中计算进度速度
                    $anomaly = $this->anomalyService->detectRapidProgressAnomaly($sessionId, 3.0);
                    if ($anomaly !== null) $detected = 1;
                    break;

                case 'window_switch':
                    // 简化实现，实际应该从行为数据中统计窗口切换次数
                    $anomaly = $this->anomalyService->detectWindowSwitchAnomaly($sessionId, 25);
                    if ($anomaly !== null) $detected = 1;
                    break;

                case 'idle_timeout':
                    // 简化实现，实际应该从行为数据中计算空闲时长
                    $anomaly = $this->anomalyService->detectIdleTimeoutAnomaly($sessionId, 700);
                    if ($anomaly !== null) $detected = 1;
                    break;

                case 'face_detect_fail':
                    // 简化实现，实际应该从人脸检测记录中统计失败次数
                    $anomaly = $this->anomalyService->detectFaceDetectFailAnomaly($sessionId, 4);
                    if ($anomaly !== null) $detected = 1;
                    break;

                case 'network_anomaly':
                    // 简化实现，实际应该从网络监控数据中获取
                    $anomaly = $this->anomalyService->detectNetworkAnomaly($sessionId, ['disconnectCount' => 6]);
                    if ($anomaly !== null) $detected = 1;
                    break;
            }

            // 自动解决轻微异常
            if ($autoResolve && $anomaly !== null && $anomaly->getSeverity() === AnomalySeverity::LOW) {
                $this->anomalyService->resolveAnomaly(
                    $anomaly->getId(),
                    '自动解决：轻微异常，不影响学习效果',
                    'system'
                );
                $resolved = 1;
            }
        }

        return ['detected' => $detected, 'resolved' => $resolved];
    }

    /**
     * 检测所有类型的异常
     */
    private function detectAllAnomalies(string $sessionId, bool $autoResolve, bool $dryRun): array
    {
        $totalDetected = 0;
        $totalResolved = 0;

        $anomalyTypes = [
            'rapid_progress',
            'window_switch', 
            'idle_timeout',
            'face_detect_fail',
            'network_anomaly'
        ];

        foreach ($anomalyTypes as $type) {
            $result = $this->detectSpecificAnomaly($sessionId, $type, $autoResolve, $dryRun);
            $totalDetected += $result['detected'];
            $totalResolved += $result['resolved'];
        }

        // 单独检测多设备异常（需要用户ID）
        if (!$dryRun) {
            $session = $this->sessionRepository->find($sessionId);
            $userId = $session->getStudent()->getId();
            $anomalies = $this->anomalyService->detectMultipleDeviceAnomaly((string) $userId);
            $totalDetected += count($anomalies);

            if ((bool) $autoResolve) {
                foreach ($anomalies as $anomaly) {
                    if ($anomaly->getSeverity() === AnomalySeverity::LOW) {
                        $this->anomalyService->resolveAnomaly(
                            $anomaly->getId(),
                            '自动解决：轻微多设备异常',
                            'system'
                        );
                        $totalResolved++;
                    }
                }
            }
        }

        return ['detected' => $totalDetected, 'resolved' => $totalResolved];
    }
} 