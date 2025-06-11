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
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Repository\LearnStatisticsRepository;
use Tourze\TrainRecordBundle\Service\LearnAnalyticsService;

#[AsCommand(
    name: 'learn:statistics',
    description: '生成学习统计数据'
)]
class LearnStatisticsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnStatisticsRepository $statisticsRepository,
        private readonly LearnAnalyticsService $analyticsService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                '统计类型 (user, course, behavior, anomaly, device, progress, duration, efficiency, completion, engagement, quality, trend)',
                'user'
            )
            ->addOption(
                'period',
                'p',
                InputOption::VALUE_OPTIONAL,
                '统计周期 (realtime, hourly, daily, weekly, monthly, quarterly, yearly)',
                'daily'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_OPTIONAL,
                '统计日期 (Y-m-d)',
                date('Y-m-d')
            )
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定用户ID（用于用户统计）'
            )
            ->addOption(
                'course-id',
                'c',
                InputOption::VALUE_OPTIONAL,
                '指定课程ID（用于课程统计）'
            )
            ->addOption(
                'days',
                null,
                InputOption::VALUE_OPTIONAL,
                '统计天数（向前追溯）',
                7
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式 (table, json, csv)',
                'table'
            )
            ->addOption(
                'save',
                's',
                InputOption::VALUE_NONE,
                '保存统计结果到数据库'
            )
            ->addOption(
                'export',
                'e',
                InputOption::VALUE_OPTIONAL,
                '导出文件路径'
            )
            ->addOption(
                'batch-generate',
                'b',
                InputOption::VALUE_NONE,
                '批量生成所有类型的统计'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getOption('type');
        $period = $input->getOption('period');
        $date = $input->getOption('date');
        $userId = $input->getOption('user-id');
        $courseId = $input->getOption('course-id');
        $days = (int) $input->getOption('days');
        $format = $input->getOption('format');
        $save = $input->getOption('save');
        $exportPath = $input->getOption('export');
        $batchGenerate = $input->getOption('batch-generate');

        $io->title('学习统计生成');

        try {
            if ($batchGenerate) {
                $result = $this->batchGenerateStatistics($date, $period, $days, $save, $io);
            } else {
                $result = $this->generateSingleStatistics(
                    $type,
                    $period,
                    $date,
                    $userId,
                    $courseId,
                    $days,
                    $format,
                    $save,
                    $exportPath,
                    $io
                );
            }

            $io->success($result['message']);
            return Command::SUCCESS;

        } catch  (\Throwable $e) {
            $this->logger->error('学习统计生成失败', [
                'type' => $type,
                'period' => $period,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('统计生成失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 生成单个统计
     */
    private function generateSingleStatistics(
        string $type,
        string $period,
        string $date,
        ?string $userId,
        ?string $courseId,
        int $days,
        string $format,
        bool $save,
        ?string $exportPath,
        SymfonyStyle $io
    ): array {
        $io->section("生成{$type}统计 - {$period}周期");

        $statisticsType = StatisticsType::from($type);
        $statisticsPeriod = StatisticsPeriod::from($period);

        // 计算日期范围
        $endDate = new \DateTime($date . ' 23:59:59');
        $startDate = (clone $endDate)->sub(new \DateInterval('P' . $days . 'D'));

        $io->text(sprintf(
            "统计范围: %s 到 %s (%d天)",
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $days
        ));

        // 生成统计数据
        $statisticsData = $this->generateStatisticsData(
            $statisticsType,
            $startDate,
            $endDate,
            $userId,
            $courseId
        );

        // 显示结果
        $this->displayStatistics($statisticsData, $format, $io);

        // 保存到数据库
        if ($save) {
            $this->saveStatistics($statisticsType, $statisticsPeriod, $statisticsData, $userId ?? $courseId ?? 'global');
            $io->note('统计数据已保存到数据库');
        }

        // 导出文件
        if ($exportPath) {
            $this->exportStatistics($statisticsData, $exportPath, $format);
            $io->note("统计数据已导出到: {$exportPath}");
        }

        return [
            'message' => sprintf('%s统计生成完成', $statisticsType->getLabel()),
            'data' => $statisticsData,
        ];
    }

    /**
     * 批量生成统计
     */
    private function batchGenerateStatistics(
        string $date,
        string $period,
        int $days,
        bool $save,
        SymfonyStyle $io
    ): array {
        $io->section('批量生成所有统计');

        $statisticsTypes = [
            StatisticsType::USER,
            StatisticsType::COURSE,
            StatisticsType::BEHAVIOR,
            StatisticsType::ANOMALY,
            StatisticsType::DEVICE,
            StatisticsType::PROGRESS,
            StatisticsType::DURATION,
        ];

        $statisticsPeriod = StatisticsPeriod::from($period);
        $endDate = new \DateTime($date . ' 23:59:59');
        $startDate = (clone $endDate)->sub(new \DateInterval('P' . $days . 'D'));

        $generatedCount = 0;
        $errorCount = 0;

        $progressBar = $io->createProgressBar(count($statisticsTypes));
        $progressBar->start();

        foreach ($statisticsTypes as $type) {
            try {
                $io->text("生成 {$type->getLabel()} 统计...");

                $statisticsData = $this->generateStatisticsData($type, $startDate, $endDate);

                if ($save) {
                    $this->saveStatistics($type, $statisticsPeriod, $statisticsData, 'global');
                }

                $generatedCount++;
            } catch  (\Throwable $e) {
                $errorCount++;
                $this->logger->error('生成统计失败', [
                    'type' => $type->value,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if ($errorCount > 0) {
            $io->warning(sprintf('生成过程中发生 %d 个错误', $errorCount));
        }

        return [
            'message' => sprintf('批量统计生成完成！成功生成 %d 个统计，失败 %d 个', $generatedCount, $errorCount),
            'generated' => $generatedCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * 生成统计数据
     */
    private function generateStatisticsData(
        StatisticsType $type,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        return match ($type) {
            StatisticsType::USER => $userId 
                ? $this->analyticsService->generateUserAnalytics($userId, $startDate, $endDate)
                : $this->generateGlobalUserStatistics($startDate, $endDate),
            
            StatisticsType::COURSE => $courseId
                ? $this->analyticsService->generateCourseAnalytics($courseId, $startDate, $endDate)
                : $this->generateGlobalCourseStatistics($startDate, $endDate),
            
            StatisticsType::BEHAVIOR => $this->generateBehaviorStatistics($startDate, $endDate, $userId),
            StatisticsType::ANOMALY => $this->generateAnomalyStatistics($startDate, $endDate, $userId),
            StatisticsType::DEVICE => $this->generateDeviceStatistics($startDate, $endDate, $userId),
            StatisticsType::PROGRESS => $this->generateProgressStatistics($startDate, $endDate, $userId, $courseId),
            StatisticsType::DURATION => $this->generateDurationStatistics($startDate, $endDate, $userId, $courseId),
            
            default => $this->analyticsService->generateSystemAnalytics($startDate, $endDate),
        };
    }

    /**
     * 显示统计结果
     */
    private function displayStatistics(array $data, string $format, SymfonyStyle $io): void
    {
        switch ($format) {
            case 'json':
                $io->text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'csv':
                $this->displayCsvFormat($data, $io);
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
        if (isset($data['overview'])) {
            $io->section('概览');
            $overview = $data['overview'];
            $rows = [];
            foreach ($overview as $key => $value) {
                $rows[] = [ucfirst($key), is_numeric($value) ? number_format($value, 2) : $value];
            }
            $io->table(['指标', '数值'], $rows);
        }

        if (isset($data['userMetrics'])) {
            $io->section('用户指标');
            $this->displayMetricsTable($data['userMetrics'], $io);
        }

        if (isset($data['courseMetrics'])) {
            $io->section('课程指标');
            $this->displayMetricsTable($data['courseMetrics'], $io);
        }

        if (isset($data['behaviorAnalysis'])) {
            $io->section('行为分析');
            $this->displayMetricsTable($data['behaviorAnalysis'], $io);
        }
    }

    /**
     * 显示指标表格
     */
    private function displayMetricsTable(array $metrics, SymfonyStyle $io): void
    {
        $rows = [];
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $rows[] = [ucfirst($key), is_numeric($value) ? number_format($value, 2) : $value];
        }
        $io->table(['指标', '数值'], $rows);
    }

    /**
     * CSV格式显示
     */
    private function displayCsvFormat(array $data, SymfonyStyle $io): void
    {
        $io->text('指标,数值');
        $this->outputCsvData($data, $io);
    }

    /**
     * 输出CSV数据
     */
    private function outputCsvData(array $data, SymfonyStyle $io, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->outputCsvData($value, $io, $fullKey);
            } else {
                $io->text(sprintf('%s,%s', $fullKey, $value));
            }
        }
    }

    /**
     * 保存统计数据
     */
    private function saveStatistics(
        StatisticsType $type,
        StatisticsPeriod $period,
        array $data,
        string $scopeId
    ): void {
        $this->analyticsService->createStatistics($type, $period, $scopeId, $data);
    }

    /**
     * 导出统计数据
     */
    private function exportStatistics(array $data, string $filePath, string $format): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch ($format) {
            case 'json':
                file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'csv':
                $this->exportCsvFormat($data, $filePath);
                break;

            default:
                throw new \InvalidArgumentException("不支持的导出格式: {$format}");
        }
    }

    /**
     * 导出CSV格式
     */
    private function exportCsvFormat(array $data, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        fputcsv($handle, ['指标', '数值']);
        
        $this->writeCsvData($data, $handle);
        
        fclose($handle);
    }

    /**
     * 写入CSV数据
     */
    private function writeCsvData(array $data, $handle, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->writeCsvData($value, $handle, $fullKey);
            } else {
                fputcsv($handle, [$fullKey, $value]);
            }
        }
    }

    /**
     * 生成全局用户统计（简化实现）
     */
    private function generateGlobalUserStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return [
            'overview' => [
                'totalUsers' => 0,
                'activeUsers' => 0,
                'newUsers' => 0,
                'userRetention' => 0,
            ],
        ];
    }

    /**
     * 生成全局课程统计（简化实现）
     */
    private function generateGlobalCourseStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return [
            'overview' => [
                'totalCourses' => 0,
                'activeCourses' => 0,
                'completionRate' => 0,
                'averageProgress' => 0,
            ],
        ];
    }

    /**
     * 生成行为统计（简化实现）
     */
    private function generateBehaviorStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId): array
    {
        return [
            'overview' => [
                'totalBehaviors' => 0,
                'suspiciousBehaviors' => 0,
                'suspiciousRate' => 0,
            ],
        ];
    }

    /**
     * 生成异常统计（简化实现）
     */
    private function generateAnomalyStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId): array
    {
        return [
            'overview' => [
                'totalAnomalies' => 0,
                'resolvedAnomalies' => 0,
                'resolutionRate' => 0,
            ],
        ];
    }

    /**
     * 生成设备统计（简化实现）
     */
    private function generateDeviceStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId): array
    {
        return [
            'overview' => [
                'totalDevices' => 0,
                'activeDevices' => 0,
                'trustedDevices' => 0,
            ],
        ];
    }

    /**
     * 生成进度统计（简化实现）
     */
    private function generateProgressStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array
    {
        return [
            'overview' => [
                'totalLessons' => 0,
                'completedLessons' => 0,
                'averageProgress' => 0,
            ],
        ];
    }

    /**
     * 生成时长统计（简化实现）
     */
    private function generateDurationStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array
    {
        return [
            'overview' => [
                'totalDuration' => 0,
                'effectiveDuration' => 0,
                'effectiveRate' => 0,
            ],
        ];
    }
} 