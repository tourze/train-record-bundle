<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorAlertHandler;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorDataCollector;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorDataDisplayer;
use Tourze\TrainRecordBundle\Service\Monitor\MonitorLogger;

#[AsCommand(
    name: self::NAME,
    description: '实时监控学习状态和系统健康'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnMonitorCommand extends Command
{
    protected const NAME = 'learn:monitor';

    private bool $shouldStop = false;

    public function __construct(
        private readonly MonitorDataCollector $dataCollector,
        private readonly MonitorDataDisplayer $dataDisplayer,
        private readonly MonitorAlertHandler $alertHandler,
        private readonly MonitorLogger $monitorLogger,
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
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '仅模拟执行，不进行实际监控'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $config = $this->extractMonitorConfig($input);

        if ($config['dryRun']) {
            $io->note('DRY RUN MODE: 仅模拟执行，不进行实际监控');

            return Command::SUCCESS;
        }

        $this->displayMonitorIntro($config, $io);
        $this->setupSignalHandlers();

        return $this->runMonitoringLoop($config, $io);
    }

    /**
     * @return array{interval: int, duration: int, alertThreshold: int, outputFormat: string|null, logFile: string|null, autoResolve: bool, quiet: bool, dryRun: bool}
     */
    private function extractMonitorConfig(InputInterface $input): array
    {
        $interval = $input->getOption('interval');
        $duration = $input->getOption('duration');
        $alertThreshold = $input->getOption('alert-threshold');
        $outputFormat = $input->getOption('output-format');
        $logFile = $input->getOption('log-file');

        return [
            'interval' => is_numeric($interval) ? (int) $interval : 30,
            'duration' => is_numeric($duration) ? (int) $duration : 0,
            'alertThreshold' => is_numeric($alertThreshold) ? (int) $alertThreshold : 10,
            'outputFormat' => is_string($outputFormat) ? $outputFormat : null,
            'logFile' => is_string($logFile) ? $logFile : null,
            'autoResolve' => (bool) $input->getOption('auto-resolve'),
            'quiet' => (bool) $input->getOption('quiet'),
            'dryRun' => (bool) $input->getOption('dry-run'),
        ];
    }

    /**
     * @param array{interval: int, duration: int, alertThreshold: int, outputFormat: string|null, logFile: string|null, autoResolve: bool, quiet: bool, dryRun: bool} $config
     */
    private function displayMonitorIntro(array $config, SymfonyStyle $io): void
    {
        if (!$config['quiet']) {
            $io->title('学习系统实时监控');
            $io->text([
                "监控间隔: {$config['interval']}秒",
                '持续时间: ' . ($config['duration'] > 0 ? "{$config['duration']}分钟" : '无限'),
                "异常阈值: {$config['alertThreshold']}",
                "输出格式: {$config['outputFormat']}",
                '自动解决: ' . ($config['autoResolve'] ? '是' : '否'),
            ]);
            $io->newLine();
        }
    }

    private function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, $this->handleSignal(...));
            pcntl_signal(SIGINT, $this->handleSignal(...));
        }
    }

    /**
     * @param array{interval: int, duration: int, alertThreshold: int, outputFormat: string|null, logFile: string|null, autoResolve: bool, quiet: bool, dryRun: bool} $config
     */
    private function runMonitoringLoop(array $config, SymfonyStyle $io): int
    {
        $startTime = time();
        $endTime = $config['duration'] > 0 ? $startTime + ($config['duration'] * 60) : 0;
        $logHandle = $this->openLogFile($config['logFile']);

        try {
            while (!$this->shouldStop) {
                if ($this->processMonitorCycle($config, $endTime, $logHandle, $io)) {
                    break;
                }
            }

            return $this->finishMonitoring($logHandle, $config['quiet'], $io);
        } catch (\Throwable $e) {
            return $this->handleMonitoringError($e, $logHandle, $io);
        }
    }

    /**
     * @param array{interval: int, duration: int, alertThreshold: int, outputFormat: string|null, logFile: string|null, autoResolve: bool, quiet: bool, dryRun: bool} $config
     * @param resource|null $logHandle
     */
    private function processMonitorCycle(array $config, int $endTime, $logHandle, SymfonyStyle $io): bool
    {
        $monitorData = $this->dataCollector->collectAllData();

        if ($this->shouldStopMonitoring($endTime)) {
            return true;
        }

        $this->processMonitorOutput($monitorData, $config, $logHandle, $io);
        $this->alertHandler->checkAlertsAndResolve(
            $monitorData,
            $config['alertThreshold'],
            $config['autoResolve'],
            $io,
            $config['quiet']
        );
        $this->waitForNextCycle($config['interval']);

        return false;
    }

    private function shouldStopMonitoring(int $endTime): bool
    {
        return $endTime > 0 && time() >= $endTime;
    }

    /**
     * @param string|null $logFile
     * @return resource|null
     */
    private function openLogFile(?string $logFile)
    {
        if (null === $logFile) {
            return null;
        }

        $handle = fopen($logFile, 'a');

        return false !== $handle ? $handle : null;
    }

    /**
     * @param array<string, mixed> $monitorData
     * @param array{interval: int, duration: int, alertThreshold: int, outputFormat: string|null, logFile: string|null, autoResolve: bool, quiet: bool, dryRun: bool} $config
     * @param resource|null $logHandle
     */
    private function processMonitorOutput(array $monitorData, array $config, $logHandle, SymfonyStyle $io): void
    {
        if (!$config['quiet']) {
            $outputFormat = $config['outputFormat'] ?? 'table';
            $this->dataDisplayer->displayMonitoringData($monitorData, $outputFormat, $io);
        }

        if (null !== $logHandle) {
            $this->monitorLogger->writeToLogFile($monitorData, $logHandle);
        }
    }

    private function waitForNextCycle(int $interval): void
    {
        sleep($interval);

        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * @param resource|null $logHandle
     */
    private function finishMonitoring($logHandle, bool $quiet, SymfonyStyle $io): int
    {
        if (null !== $logHandle) {
            fclose($logHandle);
        }

        if (!$quiet) {
            $io->success('监控已停止');
        }

        return Command::SUCCESS;
    }

    /**
     * @param resource|null $logHandle
     */
    private function handleMonitoringError(\Throwable $e, $logHandle, SymfonyStyle $io): int
    {
        $this->logger->error('学习监控失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (null !== $logHandle) {
            fclose($logHandle);
        }

        $io->error('监控失败: ' . $e->getMessage());

        return Command::FAILURE;
    }

    /**
     * 处理信号
     */
    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;

        return false;
    }
}
