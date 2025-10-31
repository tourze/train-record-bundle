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
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnAnomalyService;

#[AsCommand(
    name: self::NAME,
    description: '批量检测学习异常'
)]
#[AsCronTask(
    expression: '*/30 * * * *'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnAnomalyDetectCommand extends Command
{
    protected const NAME = 'learn:anomaly:detect';

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnAnomalyService $anomalyService,
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getOption('session-id');
        $userId = $input->getOption('user-id');
        $date = $input->getOption('date');
        $anomalyType = $input->getOption('anomaly-type');
        $batchSize = max(1, $this->getIntOption($input, 'batch-size', 100));
        $autoResolve = (bool) $input->getOption('auto-resolve');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('学习异常检测');

        if ($dryRun) {
            $io->note('运行在试运行模式，不会实际创建异常记录');
        }

        if ($autoResolve) {
            $io->note('启用自动解决轻微异常功能');
        }

        try {
            $detectedCount = 0;
            $resolvedCount = 0;

            if (null !== $sessionId) {
                // 检测单个会话
                $result = $this->detectSingleSession($this->getStringOption($input, 'session-id'), $this->getStringOption($input, 'anomaly-type'), $autoResolve, $dryRun, $io);
                $detectedCount = $result['detected'];
                $resolvedCount = $result['resolved'];
            } elseif (null !== $userId) {
                // 检测指定用户的会话
                $result = $this->detectUserSessions($this->getStringOption($input, 'user-id'), $this->getStringOption($input, 'date'), $this->getStringOption($input, 'anomaly-type'), $batchSize, $autoResolve, $dryRun, $io);
                $detectedCount = $result['detected'];
                $resolvedCount = $result['resolved'];
            } else {
                // 检测指定日期的所有会话
                $result = $this->detectDateSessions(
                    is_string($date) ? $date : date('Y-m-d'),
                    is_string($anomalyType) ? $anomalyType : null,
                    $batchSize,
                    $autoResolve,
                    $dryRun,
                    $io
                );
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
     *
     * @return array{detected: int, resolved: int}
     */
    private function detectSingleSession(
        string $sessionId,
        ?string $anomalyType,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            $io->error("会话 {$sessionId} 不存在");

            return ['detected' => 0, 'resolved' => 0];
        }

        $io->text("检测会话: {$sessionId}");

        $detected = 0;
        $resolved = 0;

        if (null !== $anomalyType) {
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
     *
     * @return array{detected: int, resolved: int}
     */
    private function detectUserSessions(
        string $userId,
        string $date,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByUserAndDateRange($userId, $startDate, $endDate);

        $io->text(sprintf('找到用户 %s 在 %s 的 %d 个会话', $userId, $date, count($sessions)));

        return $this->detectSessions($sessions, $anomalyType, max(1, $batchSize), $autoResolve, $dryRun, $io);
    }

    /**
     * 检测日期会话
     *
     * @return array{detected: int, resolved: int}
     */
    private function detectDateSessions(
        string $date,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $startDate = new \DateTimeImmutable($date . ' 00:00:00');
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');

        $sessions = $this->sessionRepository->findByDateRange($startDate, $endDate);

        $io->text(sprintf('找到 %s 的 %d 个会话', $date, count($sessions)));

        return $this->detectSessions($sessions, $anomalyType, max(1, $batchSize), $autoResolve, $dryRun, $io);
    }

    /**
     * 批量检测会话
     *
     * @param array<LearnSession> $sessions
     * @param int<1, max> $batchSize
     * @return array{detected: int, resolved: int}
     */
    private function detectSessions(
        array $sessions,
        ?string $anomalyType,
        int $batchSize,
        bool $autoResolve,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $total = count($sessions);
        $totalDetected = 0;
        $totalResolved = 0;

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($sessions, $batchSize) as $batch) {
            foreach ($batch as $session) {
                $sessionId = $session->getId();
                if (null === $sessionId) {
                    continue;
                }

                try {
                    $result = $this->detectSessionAnomalies($sessionId, $anomalyType, $autoResolve, $dryRun);
                    $totalDetected += $result['detected'];
                    $totalResolved += $result['resolved'];
                } catch (\Throwable $e) {
                    $this->logger->error('检测会话异常失败', [
                        'sessionId' => $sessionId,
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
     * 检测单个会话的异常
     *
     * @return array{detected: int, resolved: int}
     */
    private function detectSessionAnomalies(string $sessionId, ?string $anomalyType, bool $autoResolve, bool $dryRun): array
    {
        if (null !== $anomalyType) {
            return $this->detectSpecificAnomaly($sessionId, $anomalyType, $autoResolve, $dryRun);
        }

        return $this->detectAllAnomalies($sessionId, $autoResolve, $dryRun);
    }

    /**
     * 检测指定类型的异常
     *
     * @return array{detected: int, resolved: int}
     */
    private function detectSpecificAnomaly(string $sessionId, string $anomalyType, bool $autoResolve, bool $dryRun): array
    {
        if ($dryRun) {
            return ['detected' => 0, 'resolved' => 0];
        }

        $anomaly = $this->detectAnomalyByType($sessionId, $anomalyType);
        $detected = null !== $anomaly ? 1 : 0;
        $resolved = 0;

        if ($autoResolve && null !== $anomaly && AnomalySeverity::LOW === $anomaly->getSeverity()) {
            $anomalyId = $anomaly->getId();
            if (null !== $anomalyId) {
                $this->anomalyService->resolveAnomaly(
                    $anomalyId,
                    '自动解决：轻微异常，不影响学习效果',
                    'system'
                );
            }
            $resolved = 1;
        }

        return ['detected' => $detected, 'resolved' => $resolved];
    }

    private function detectAnomalyByType(string $sessionId, string $anomalyType): ?LearnAnomaly
    {
        switch ($anomalyType) {
            case 'multiple_device':
                $session = $this->sessionRepository->find($sessionId);
                if (null === $session) {
                    return null;
                }
                $userId = $session->getStudent()->getUserIdentifier();
                $anomalies = $this->anomalyService->detectMultipleDeviceAnomaly((string) $userId);

                // 更安全的数组访问：先检查非空，再检查第一个元素存在性和类型
                if ([] !== $anomalies && isset($anomalies[0]) && $anomalies[0] instanceof LearnAnomaly) {
                    return $anomalies[0];
                }

                return null;

            case 'rapid_progress':
                return $this->anomalyService->detectRapidProgressAnomaly($sessionId, 3.0);

            case 'window_switch':
                return $this->anomalyService->detectWindowSwitchAnomaly($sessionId, 25);

            case 'idle_timeout':
                return $this->anomalyService->detectIdleTimeoutAnomaly($sessionId, 700);

            case 'face_detect_fail':
                return $this->anomalyService->detectFaceDetectFailAnomaly($sessionId, 4);

            case 'network_anomaly':
                return $this->anomalyService->detectNetworkAnomaly($sessionId, ['disconnectCount' => 6]);

            default:
                return null;
        }
    }

    /**
     * 检测所有类型的异常
     *
     * @return array{detected: int, resolved: int}
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
            'network_anomaly',
        ];

        foreach ($anomalyTypes as $type) {
            $result = $this->detectSpecificAnomaly($sessionId, $type, $autoResolve, $dryRun);
            $totalDetected += $result['detected'];
            $totalResolved += $result['resolved'];
        }

        $multiDeviceResult = $this->detectMultiDeviceAnomalies($sessionId, $autoResolve, $dryRun);
        $totalDetected += $multiDeviceResult['detected'];
        $totalResolved += $multiDeviceResult['resolved'];

        return ['detected' => $totalDetected, 'resolved' => $totalResolved];
    }

    /**
     * @return array{detected: int, resolved: int}
     */
    private function detectMultiDeviceAnomalies(string $sessionId, bool $autoResolve, bool $dryRun): array
    {
        if ($dryRun) {
            return ['detected' => 0, 'resolved' => 0];
        }

        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            return ['detected' => 0, 'resolved' => 0];
        }
        $userId = $session->getStudent()->getUserIdentifier();
        $anomalies = $this->anomalyService->detectMultipleDeviceAnomaly((string) $userId);
        $detected = count($anomalies);
        $resolved = 0;

        if ($autoResolve) {
            /** @var array<LearnAnomaly> $anomalies */
            $resolved = $this->resolveMultiDeviceAnomalies($anomalies);
        }

        return ['detected' => $detected, 'resolved' => $resolved];
    }

    /**
     * @param array<LearnAnomaly> $anomalies
     */
    private function resolveMultiDeviceAnomalies(array $anomalies): int
    {
        $resolved = 0;
        foreach ($anomalies as $anomaly) {
            if (AnomalySeverity::LOW === $anomaly->getSeverity()) {
                $anomalyId = $anomaly->getId();
                if (null !== $anomalyId) {
                    $this->anomalyService->resolveAnomaly(
                        $anomalyId,
                        '自动解决：轻微多设备异常',
                        'system'
                    );
                }
                ++$resolved;
            }
        }

        return $resolved;
    }

    /**
     * 安全获取字符串选项
     */
    private function getStringOption(InputInterface $input, string $name, string $default = ''): string
    {
        $value = $input->getOption($name);

        return is_string($value) ? $value : $default;
    }

    /**
     * 安全获取整数选项
     */
    private function getIntOption(InputInterface $input, string $name, int $default = 0): int
    {
        $value = $input->getOption($name);

        return is_numeric($value) ? (int) $value : $default;
    }
}
