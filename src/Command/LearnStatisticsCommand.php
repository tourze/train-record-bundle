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
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataCollector;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataDisplayer;
use Tourze\TrainRecordBundle\Service\Statistics\StatisticsDataProcessor;

#[AsCommand(
    name: self::NAME,
    description: '生成学习统计数据'
)]
#[AsCronTask(
    expression: '0 3 * * *'
)]
#[AsCronTask(
    expression: '0 4 * * 0'
)]
#[AsCronTask(
    expression: '0 5 1 * *'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnStatisticsCommand extends Command
{
    protected const NAME = 'learn:statistics';

    public function __construct(
        private readonly StatisticsDataCollector $dataCollector,
        private readonly StatisticsDataDisplayer $dataDisplayer,
        private readonly StatisticsDataProcessor $dataProcessor,
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
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '仅模拟执行，不进行实际统计计算'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = $this->parseCommandOptions($input);

        if ($options['dryRun']) {
            $io->note('DRY RUN MODE: 仅模拟执行，不进行实际统计计算');

            return Command::SUCCESS;
        }

        $io->title('学习统计生成');

        try {
            $result = $this->executeStatisticsGeneration($options, $io);
            $this->displaySuccessMessage($result, $io);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleStatisticsError($e, $options, $io);

            return Command::FAILURE;
        }
    }

    /**
     * 解析命令选项
     * @return array{type: string, period: string, date: string, userId: ?string, courseId: ?string, days: int, format: string, save: bool, exportPath: ?string, batchGenerate: bool, dryRun: bool}
     */
    private function parseCommandOptions(InputInterface $input): array
    {
        $typeOption = $input->getOption('type');
        $periodOption = $input->getOption('period');
        $dateOption = $input->getOption('date');
        $userIdOption = $input->getOption('user-id');
        $courseIdOption = $input->getOption('course-id');
        $daysOption = $input->getOption('days');
        $formatOption = $input->getOption('format');
        $exportPathOption = $input->getOption('export');

        return [
            'type' => is_string($typeOption) ? $typeOption : 'user',
            'period' => is_string($periodOption) ? $periodOption : 'daily',
            'date' => is_string($dateOption) ? $dateOption : date('Y-m-d'),
            'userId' => is_string($userIdOption) ? $userIdOption : null,
            'courseId' => is_string($courseIdOption) ? $courseIdOption : null,
            'days' => is_numeric($daysOption) ? (int) $daysOption : 7,
            'format' => is_string($formatOption) ? $formatOption : 'table',
            'save' => (bool) $input->getOption('save'),
            'exportPath' => is_string($exportPathOption) ? $exportPathOption : null,
            'batchGenerate' => (bool) $input->getOption('batch-generate'),
            'dryRun' => (bool) $input->getOption('dry-run'),
        ];
    }

    /**
     * 执行统计生成
     * @param array{type: string, period: string, date: string, userId: ?string, courseId: ?string, days: int, format: string, save: bool, exportPath: ?string, batchGenerate: bool, dryRun: bool} $options
     * @return array<string, mixed>
     */
    private function executeStatisticsGeneration(array $options, SymfonyStyle $io): array
    {
        if ($options['batchGenerate']) {
            return $this->executeBatchGeneration($options, $io);
        }

        return $this->executeSingleGeneration($options, $io);
    }

    /**
     * 执行批量生成
     * @param array{type: string, period: string, date: string, userId: ?string, courseId: ?string, days: int, format: string, save: bool, exportPath: ?string, batchGenerate: bool, dryRun: bool} $options
     * @return array<string, mixed>
     */
    private function executeBatchGeneration(array $options, SymfonyStyle $io): array
    {
        return $this->batchGenerateStatistics(
            $options['date'],
            $options['period'],
            $options['days'],
            $options['save'],
            $io
        );
    }

    /**
     * 执行单个生成
     * @param array{type: string, period: string, date: string, userId: ?string, courseId: ?string, days: int, format: string, save: bool, exportPath: ?string, batchGenerate: bool, dryRun: bool} $options
     * @return array<string, mixed>
     */
    private function executeSingleGeneration(array $options, SymfonyStyle $io): array
    {
        return $this->generateSingleStatistics(
            $options['type'],
            $options['period'],
            $options['date'],
            $options['userId'],
            $options['courseId'],
            $options['days'],
            $options['format'],
            $options['save'],
            $options['exportPath'],
            $io
        );
    }

    /**
     * 显示成功消息
     * @param array<string, mixed> $result
     */
    private function displaySuccessMessage(array $result, SymfonyStyle $io): void
    {
        $message = isset($result['message']) && is_string($result['message']) ? $result['message'] : '统计生成完成';
        $io->success($message);
    }

    /**
     * 处理统计错误
     * @param array{type: string, period: string, date: string, userId: ?string, courseId: ?string, days: int, format: string, save: bool, exportPath: ?string, batchGenerate: bool, dryRun: bool} $options
     */
    private function handleStatisticsError(\Throwable $e, array $options, SymfonyStyle $io): void
    {
        $this->logger->error('学习统计生成失败', [
            'type' => $options['type'],
            'period' => $options['period'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $io->error('统计生成失败: ' . $e->getMessage());
    }

    /**
     * 生成单个统计
     * @return array<string, mixed>
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
        SymfonyStyle $io,
    ): array {
        $this->displayStatisticsHeader($type, $period, $io);

        [$statisticsType, $statisticsPeriod, $startDate, $endDate] = $this->prepareStatisticsParameters($type, $period, $date, $days, $io);

        $this->displayDateRange($startDate, $endDate, $days, $io);

        $statisticsData = $this->dataCollector->generateStatisticsData($statisticsType, $startDate, $endDate, $userId, $courseId);

        $this->dataDisplayer->displayStatistics($statisticsData, $format, $io);
        $this->dataProcessor->handleStatisticsPersistence($statisticsType, $statisticsPeriod, $statisticsData, $userId, $courseId, $save, $io);
        $this->dataProcessor->handleStatisticsExport($statisticsData, $exportPath, $format, $io);

        return [
            'message' => sprintf('%s统计生成完成', $statisticsType->getLabel()),
            'data' => $statisticsData,
        ];
    }

    /**
     * 显示统计头部
     */
    private function displayStatisticsHeader(string $type, string $period, SymfonyStyle $io): void
    {
        $io->section("生成{$type}统计 - {$period}周期");
    }

    /**
     * 准备统计参数
     * @return array{StatisticsType, StatisticsPeriod, \DateTimeImmutable, \DateTimeImmutable}
     */
    private function prepareStatisticsParameters(string $type, string $period, string $date, int $days, SymfonyStyle $io): array
    {
        $statisticsType = StatisticsType::from($type);
        $statisticsPeriod = StatisticsPeriod::from($period);

        $endDate = new \DateTimeImmutable($date . ' 23:59:59');
        $startDate = (clone $endDate)->sub(new \DateInterval('P' . $days . 'D'));

        return [$statisticsType, $statisticsPeriod, $startDate, $endDate];
    }

    /**
     * 显示日期范围
     */
    private function displayDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, int $days, SymfonyStyle $io): void
    {
        $io->text(sprintf(
            '统计范围: %s 到 %s (%d天)',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $days
        ));
    }

    /**
     * 批量生成统计
     * @return array<string, mixed>
     */
    private function batchGenerateStatistics(
        string $date,
        string $period,
        int $days,
        bool $save,
        SymfonyStyle $io,
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
        $endDate = new \DateTimeImmutable($date . ' 23:59:59');
        $startDate = (clone $endDate)->sub(new \DateInterval('P' . $days . 'D'));

        $generatedCount = 0;
        $errorCount = 0;

        $progressBar = $io->createProgressBar(count($statisticsTypes));
        $progressBar->start();

        foreach ($statisticsTypes as $type) {
            try {
                $io->text("生成 {$type->getLabel()} 统计...");

                $statisticsData = $this->dataCollector->generateStatisticsData($type, $startDate, $endDate);

                if ($save) {
                    $this->dataProcessor->saveStatistics($type, $statisticsPeriod, $statisticsData, 'global');
                }

                ++$generatedCount;
            } catch (\Throwable $e) {
                ++$errorCount;
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
}
