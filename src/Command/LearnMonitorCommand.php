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
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnAnomalyService;

#[AsCommand(
    name: 'learn:monitor',
    description: '实时监控学习状态和系统健康'
)]
class LearnMonitorCommand extends Command
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnAnomalyRepository $anomalyRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnDeviceRepository $deviceRepository,
        private readonly LearnAnomalyService $anomalyService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                '监控间隔（秒）',
                30
            )
            ->addOption(
                'duration',
                'd',
                InputOption::VALUE_OPTIONAL,
                '监控持续时间（分钟，0表示无限）',
                0
            )
            ->addOption(
                'alert-threshold',
                't',
                InputOption::VALUE_OPTIONAL,
                '异常告警阈值',
                10
            )
            ->addOption(
                'output-format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式 (table, json, simple)',
                'table'
            )
            ->addOption(
                'log-file',
                'l',
                InputOption::VALUE_OPTIONAL,
                '监控日志文件路径'
            )
            ->addOption(
                'auto-resolve',
                'a',
                InputOption::VALUE_NONE,
                '自动解决轻微异常'
            )
            ->addOption(
                'quiet',
                'q',
                InputOption::VALUE_NONE,
                '静默模式，只输出异常信息'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $interval = (int) $input->getOption('interval');
        $duration = (int) $input->getOption('duration');
        $alertThreshold = (int) $input->getOption('alert-threshold');
        $outputFormat = $input->getOption('output-format');
        $logFile = $input->getOption('log-file');
        $autoResolve = $input->getOption('auto-resolve');
        $quiet = $input->getOption('quiet');

        if (!$quiet) {
            $io->title('学习系统实时监控');
            $io->text([
                "监控间隔: {$interval}秒",
                "持续时间: " . ($duration > 0 ? "{$duration}分钟" : "无限"),
                "异常阈值: {$alertThreshold}",
                "输出格式: {$outputFormat}",
                "自动解决: " . ($autoResolve ? '是' : '否'),
            ]);
            $io->newLine();
        }

        // 设置信号处理
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }

        $startTime = time();
        $endTime = $duration > 0 ? $startTime + ($duration * 60) : 0;
        $logHandle = $logFile ? fopen($logFile, 'a') : null;

        try {
            while (!$this->shouldStop) {
                $monitorData = $this->collectMonitoringData();
                
                // 检查是否需要停止
                if ($endTime > 0 && time() >= $endTime) {
                    break;
                }

                // 输出监控信息
                if (!$quiet) {
                    $this->displayMonitoringData($monitorData, $outputFormat, $io);
                }

                // 记录到日志文件
                if ($logHandle) {
                    $this->writeToLogFile($monitorData, $logHandle);
                }

                // 检查异常并告警
                $this->checkAlertsAndResolve($monitorData, $alertThreshold, $autoResolve, $io, $quiet);

                // 等待下一次监控
                sleep($interval);

                // 处理信号
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }

            if ($logHandle) {
                fclose($logHandle);
            }

            if (!$quiet) {
                $io->success('监控已停止');
            }

            return Command::SUCCESS;

        } catch  (\Throwable $e) {
            $this->logger->error('学习监控失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($logHandle) {
                fclose($logHandle);
            }

            $io->error('监控失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 收集监控数据
     */
    private function collectMonitoringData(): array
    {
        $now = new \DateTime();
        $oneHourAgo = (clone $now)->sub(new \DateInterval('PT1H'));

        // 活跃会话统计
        $activeSessions = $this->sessionRepository->findActiveSessions();
        $recentSessions = $this->sessionRepository->findByDateRange($oneHourAgo, $now);

        // 异常统计
        $recentAnomalies = $this->anomalyRepository->findByDateRange($oneHourAgo, $now);
        $unresolvedAnomalies = $this->anomalyRepository->findUnresolved();

        // 行为统计
        $recentBehaviors = $this->behaviorRepository->findByDateRange($oneHourAgo, $now);
        $suspiciousBehaviors = $this->behaviorRepository->findSuspiciousByDateRange($oneHourAgo, $now);

        // 设备统计
        $activeDevices = $this->deviceRepository->findActive();
        $recentDevices = $this->deviceRepository->findByLastSeenAfter($oneHourAgo);

        // 系统健康指标
        $systemHealth = $this->calculateSystemHealth($activeSessions, $recentAnomalies, $suspiciousBehaviors);

        return [
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'sessions' => [
                'active' => count($activeSessions),
                'recent' => count($recentSessions),
                'details' => $this->getSessionDetails($activeSessions),
            ],
            'anomalies' => [
                'recent' => count($recentAnomalies),
                'unresolved' => count($unresolvedAnomalies),
                'types' => $this->getAnomalyTypeDistribution($recentAnomalies),
                'severity' => $this->getAnomalySeverityDistribution($recentAnomalies),
            ],
            'behaviors' => [
                'total' => count($recentBehaviors),
                'suspicious' => count($suspiciousBehaviors),
                'suspiciousRate' => count($recentBehaviors) > 0 ? round(count($suspiciousBehaviors) / count($recentBehaviors) * 100, 2) : 0,
            ],
            'devices' => [
                'active' => count($activeDevices),
                'recent' => count($recentDevices),
                'types' => $this->getDeviceTypeDistribution($activeDevices),
            ],
            'system' => $systemHealth,
        ];
    }

    /**
     * 显示监控数据
     */
    private function displayMonitoringData(array $data, string $format, SymfonyStyle $io): void
    {
        switch ($format) {
            case 'json':
                $io->text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'simple':
                $io->text(sprintf(
                    '[%s] 活跃会话: %d, 异常: %d, 可疑行为: %d, 系统状态: %s',
                    $data['timestamp'],
                    $data['sessions']['active'],
                    $data['anomalies']['recent'],
                    $data['behaviors']['suspicious'],
                    $data['system']['status']
                ));
                break;

            case 'table':
            default:
                $this->displayTableFormat($data, $io);
                break;
        }
    }

    /**
     * 表格格式显示
     */
    private function displayTableFormat(array $data, SymfonyStyle $io): void
    {
        $io->section('系统监控 - ' . $data['timestamp']);

        // 会话统计
        $io->table(['会话指标', '数值'], [
            ['活跃会话', $data['sessions']['active']],
            ['最近1小时会话', $data['sessions']['recent']],
        ]);

        // 异常统计
        $io->table(['异常指标', '数值'], [
            ['最近1小时异常', $data['anomalies']['recent']],
            ['未解决异常', $data['anomalies']['unresolved']],
        ]);

        // 行为统计
        $io->table(['行为指标', '数值'], [
            ['最近1小时行为', $data['behaviors']['total']],
            ['可疑行为', $data['behaviors']['suspicious']],
            ['可疑率', $data['behaviors']['suspiciousRate'] . '%'],
        ]);

        // 设备统计
        $io->table(['设备指标', '数值'], [
            ['活跃设备', $data['devices']['active']],
            ['最近活跃设备', $data['devices']['recent']],
        ]);

        // 系统健康
        $healthColor = match ($data['system']['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };

        $io->text(sprintf(
            '系统状态: <%s>%s</> (健康分数: %d/100)',
            $healthColor,
            strtoupper($data['system']['status']),
            $data['system']['score']
        ));

        $io->newLine();
    }

    /**
     * 写入日志文件
     */
    private function writeToLogFile(array $data, $handle): void
    {
        $logLine = sprintf(
            "[%s] Sessions: %d, Anomalies: %d, Suspicious: %d, Health: %s (%d)\n",
            $data['timestamp'],
            $data['sessions']['active'],
            $data['anomalies']['recent'],
            $data['behaviors']['suspicious'],
            $data['system']['status'],
            $data['system']['score']
        );

        fwrite($handle, $logLine);
        fflush($handle);
    }

    /**
     * 检查告警并自动解决
     */
    private function checkAlertsAndResolve(
        array $data,
        int $alertThreshold,
        bool $autoResolve,
        SymfonyStyle $io,
        bool $quiet
    ): void {
        $alerts = [];

        // 检查异常数量
        if ($data['anomalies']['recent'] >= $alertThreshold) {
            $alerts[] = sprintf('异常数量过多: %d (阈值: %d)', $data['anomalies']['recent'], $alertThreshold);
        }

        // 检查可疑行为率
        if ($data['behaviors']['suspiciousRate'] > 30) {
            $alerts[] = sprintf('可疑行为率过高: %.2f%%', $data['behaviors']['suspiciousRate']);
        }

        // 检查系统健康状态
        if ($data['system']['status'] === 'critical') {
            $alerts[] = '系统状态严重异常';
        } elseif ($data['system']['status'] === 'warning') {
            $alerts[] = '系统状态警告';
        }

        // 输出告警
        if (!empty($alerts)) {
            if (!$quiet) {
                $io->warning('检测到异常:');
                foreach ($alerts as $alert) {
                    $io->text('- ' . $alert);
                }
            }

            // 记录告警日志
            $this->logger->warning('系统监控告警', [
                'alerts' => $alerts,
                'data' => $data,
            ]);

            // 自动解决
            if ($autoResolve) {
                $this->performAutoResolve($data, $io, $quiet);
            }
        }
    }

    /**
     * 执行自动解决
     */
    private function performAutoResolve(array $data, SymfonyStyle $io, bool $quiet): void
    {
        $resolvedCount = 0;

        // 自动解决轻微异常
        $unresolvedAnomalies = $this->anomalyRepository->findUnresolved();
        foreach ($unresolvedAnomalies as $anomaly) {
            if ($anomaly->getSeverity()->value === 'low') {
                try {
                    $this->anomalyService->resolveAnomaly(
                        $anomaly->getId(),
                        '自动解决：系统监控检测到轻微异常',
                        'system_monitor'
                    );
                    $resolvedCount++;
                } catch  (\Throwable $e) {
                    $this->logger->error('自动解决异常失败', [
                        'anomalyId' => $anomaly->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($resolvedCount > 0 && !$quiet) {
            $io->note("自动解决了 {$resolvedCount} 个轻微异常");
        }
    }

    /**
     * 获取会话详情
     */
    private function getSessionDetails(array $sessions): array
    {
        $details = [
            'byUser' => [],
            'byCourse' => [],
            'avgDuration' => 0,
        ];

        $totalDuration = 0;
        foreach ($sessions as $session) {
            $userId = $session->getStudent()->getId();
            $courseId = $session->getCourse()->getId();

            $details['byUser'][$userId] = ($details['byUser'][$userId] ?? 0) + 1;
            $details['byCourse'][$courseId] = ($details['byCourse'][$courseId] ?? 0) + 1;
            $totalDuration += $session->getTotalDuration();
        }

        if (count($sessions) > 0) {
            $details['avgDuration'] = round($totalDuration / count($sessions), 2);
        }

        return $details;
    }

    /**
     * 获取异常类型分布
     */
    private function getAnomalyTypeDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $type = $anomaly->getAnomalyType()->value;
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }
        return $distribution;
    }

    /**
     * 获取异常严重程度分布
     */
    private function getAnomalySeverityDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly->getSeverity()->value;
            $distribution[$severity] = ($distribution[$severity] ?? 0) + 1;
        }
        return $distribution;
    }

    /**
     * 获取设备类型分布
     */
    private function getDeviceTypeDistribution(array $devices): array
    {
        $distribution = [];
        foreach ($devices as $device) {
            $type = $device->getDeviceType();
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }
        return $distribution;
    }

    /**
     * 计算系统健康状态
     */
    private function calculateSystemHealth(array $activeSessions, array $recentAnomalies, array $suspiciousBehaviors): array
    {
        $score = 100;
        $issues = [];

        // 异常数量影响
        $anomalyCount = count($recentAnomalies);
        if ($anomalyCount > 20) {
            $score -= 30;
            $issues[] = '异常数量过多';
        } elseif ($anomalyCount > 10) {
            $score -= 15;
            $issues[] = '异常数量较多';
        }

        // 可疑行为影响
        $totalBehaviors = count($suspiciousBehaviors) > 0 ? count($suspiciousBehaviors) * 10 : 1; // 估算总行为数
        $suspiciousRate = count($suspiciousBehaviors) / $totalBehaviors * 100;
        if ($suspiciousRate > 50) {
            $score -= 25;
            $issues[] = '可疑行为率过高';
        } elseif ($suspiciousRate > 30) {
            $score -= 10;
            $issues[] = '可疑行为率较高';
        }

        // 活跃会话数影响（太少或太多都不好）
        $sessionCount = count($activeSessions);
        if ($sessionCount > 1000) {
            $score -= 10;
            $issues[] = '并发会话过多';
        } elseif ($sessionCount === 0) {
            $score -= 5;
            $issues[] = '无活跃会话';
        }

        // 确定状态
        $status = match (true) {
            $score >= 80 => 'healthy',
            $score >= 60 => 'warning',
            default => 'critical',
        };

        return [
            'score' => max(0, $score),
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /**
     * 处理信号
     */
    public function handleSignal(int $signal): void
    {
        $this->shouldStop = true;
    }
} 