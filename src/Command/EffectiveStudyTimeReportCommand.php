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
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeService;

#[AsCommand(
    name: self::NAME,
    description: '生成有效学时统计报告'
)]
#[WithMonologChannel(channel: 'train_record')]
class EffectiveStudyTimeReportCommand extends Command
{
    protected const NAME = 'train-record:effective-study-time:report';

    public function __construct(
        private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly EffectiveStudyTimeService $studyTimeService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定用户ID生成个人报告'
            )
            ->addOption(
                'course-id',
                'c',
                InputOption::VALUE_OPTIONAL,
                '指定课程ID生成课程报告'
            )
            ->addOption(
                'start-date',
                's',
                InputOption::VALUE_OPTIONAL,
                '开始日期 (Y-m-d)',
                date('Y-m-d', strtotime('-7 days'))
            )
            ->addOption(
                'end-date',
                null,
                InputOption::VALUE_OPTIONAL,
                '结束日期 (Y-m-d)',
                date('Y-m-d')
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式 (table|json|csv)',
                'table'
            )
            ->addOption(
                'output-file',
                'o',
                InputOption::VALUE_OPTIONAL,
                '输出文件路径'
            )
            ->addOption(
                'include-details',
                null,
                InputOption::VALUE_NONE,
                '包含详细信息'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = $this->parseReportOptions($input);

        $io->title('有效学时统计报告');

        try {
            $this->generateAndDisplayReport($options, $io);
            $io->success('报告生成完成');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleReportError($e, $io);

            return Command::FAILURE;
        }
    }

    /**
     * 解析报告选项
     * @return array{userId: ?string, courseId: ?string, startDate: \DateTimeImmutable, endDate: \DateTimeImmutable, format: string, outputFile: ?string, includeDetails: bool}
     */
    private function parseReportOptions(InputInterface $input): array
    {
        $startDateOption = $input->getOption('start-date');
        $endDateOption = $input->getOption('end-date');

        return [
            'userId' => is_string($input->getOption('user-id')) ? $input->getOption('user-id') : null,
            'courseId' => is_string($input->getOption('course-id')) ? $input->getOption('course-id') : null,
            'startDate' => new \DateTimeImmutable(is_string($startDateOption) ? $startDateOption : date('Y-m-d', strtotime('-7 days'))),
            'endDate' => new \DateTimeImmutable(is_string($endDateOption) ? $endDateOption : date('Y-m-d')),
            'format' => is_string($input->getOption('format')) ? $input->getOption('format') : 'table',
            'outputFile' => is_string($input->getOption('output-file')) ? $input->getOption('output-file') : null,
            'includeDetails' => (bool) $input->getOption('include-details'),
        ];
    }

    /**
     * 生成并显示报告
     * @param array{userId: ?string, courseId: ?string, startDate: \DateTimeImmutable, endDate: \DateTimeImmutable, format: string, outputFile: ?string, includeDetails: bool} $options
     */
    private function generateAndDisplayReport(array $options, SymfonyStyle $io): void
    {
        if (null !== $options['userId']) {
            $report = $this->generateUserReport($options['userId'], $options['startDate'], $options['endDate'], $options['includeDetails']);
            $this->displayUserReport($report, $io, $options['format'], $options['outputFile']);
        } elseif (null !== $options['courseId']) {
            $report = $this->generateCourseReport($options['courseId'], $options['includeDetails']);
            $this->displayCourseReport($report, $io, $options['format'], $options['outputFile']);
        } else {
            $report = $this->generateOverallReport($options['startDate'], $options['endDate'], $options['includeDetails']);
            $this->displayOverallReport($report, $io, $options['format'], $options['outputFile']);
        }
    }

    /**
     * 处理报告错误
     */
    private function handleReportError(\Throwable $e, SymfonyStyle $io): void
    {
        $this->logger->error('生成学时报告失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $io->error('报告生成失败: ' . $e->getMessage());
    }

    /**
     * 生成用户报告
     *
     * @return array{type: string, user_id: string, period: array{start: string, end: string}, summary: array<string, mixed>, details?: array<array<string, mixed>>}
     */
    private function generateUserReport(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate, bool $includeDetails): array
    {
        $stats = $this->studyTimeService->getUserStudyTimeStats($userId, $startDate, $endDate);

        $report = [
            'type' => 'user',
            'user_id' => $userId,
            'period' => $this->formatPeriod($startDate, $endDate),
            'summary' => $this->buildUserReportSummary($stats),
        ];

        if ($includeDetails) {
            $report['details'] = $this->generateUserReportDetails($userId, $startDate);
        }

        return $report;
    }

    /**
     * 格式化时间周期
     * @return array{start: string, end: string}
     */
    private function formatPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * 构建用户报告摘要
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function buildUserReportSummary(array $stats): array
    {
        return [
            'total_records' => $this->getIntValue($stats, 'totalRecords'),
            'total_time_hours' => round($this->getNumericValue($stats, 'totalTime') / 3600, 2),
            'effective_time_hours' => round($this->getNumericValue($stats, 'effectiveTime') / 3600, 2),
            'invalid_time_hours' => round($this->getNumericValue($stats, 'invalidTime') / 3600, 2),
            'efficiency_rate' => $this->calculateEfficiencyRate($stats),
            'avg_quality_score' => round($this->getNumericValue($stats, 'avgQuality'), 1),
            'avg_focus_score' => round($this->getNumericValue($stats, 'avgFocus'), 3),
            'avg_interaction_score' => round($this->getNumericValue($stats, 'avgInteraction'), 3),
            'avg_continuity_score' => round($this->getNumericValue($stats, 'avgContinuity'), 3),
        ];
    }

    /**
     * 生成用户报告详情
     * @return array<array<string, mixed>>
     */
    private function generateUserReportDetails(string $userId, \DateTimeInterface $startDate): array
    {
        $records = $this->recordRepository->findByUserAndDate($userId, $startDate);

        return array_map(function ($record): array {
            return [
                'id' => $record->getId(),
                'session_id' => $record->getSession()->getId(),
                'course_title' => $record->getCourse()->getTitle(),
                'lesson_title' => $record->getLesson()->getTitle(),
                'date' => $record->getStudyDate()->format('Y-m-d'),
                'duration_minutes' => round($record->getTotalDuration() / 60, 1),
                'effective_minutes' => round($record->getEffectiveDuration() / 60, 1),
                'status' => $record->getStatus()->getLabel(),
                'quality_score' => $record->getQualityScore(),
            ];
        }, $records);
    }

    /**
     * 生成课程报告
     *
     * @return array{type: string, course_id: string, summary: array<string, mixed>, details?: array<array<string, mixed>>}
     */
    private function generateCourseReport(string $courseId, bool $includeDetails): array
    {
        $stats = $this->studyTimeService->getCourseStudyTimeStats($courseId);

        $report = [
            'type' => 'course',
            'course_id' => $courseId,
            'summary' => [
                'total_students' => $this->getIntValue($stats, 'totalStudents'),
                'total_effective_time_hours' => round($this->getNumericValue($stats, 'totalEffectiveTime') / 3600, 2),
                'avg_effective_time_hours' => round($this->getNumericValue($stats, 'avgEffectiveTime') / 3600, 2),
                'total_study_time_hours' => round($this->getNumericValue($stats, 'totalStudyTime') / 3600, 2),
                'avg_quality_score' => round($this->getNumericValue($stats, 'avgQuality'), 1),
            ],
        ];

        if ($includeDetails) {
            $records = $this->recordRepository->findByCourse($courseId, 100);
            $report['details'] = array_map(function ($record): array {
                return [
                    'user_id' => $record->getUserId(),
                    'lesson_title' => $record->getLesson()->getTitle(),
                    'date' => $record->getStudyDate()->format('Y-m-d'),
                    'effective_minutes' => round($record->getEffectiveDuration() / 60, 1),
                    'status' => $record->getStatus()->getLabel(),
                    'quality_score' => $record->getQualityScore(),
                ];
            }, $records);
        }

        return $report;
    }

    /**
     * 生成整体报告
     *
     * @return array{type: string, period: array{start: string, end: string}, summary: array<string, mixed>, low_quality_records?: array<array<string, mixed>>, pending_review_records?: array<array<string, mixed>>}
     */
    private function generateOverallReport(\DateTimeImmutable $startDate, \DateTimeInterface $endDate, bool $includeDetails): array
    {
        $reportData = $this->collectOverallReportData($startDate, $endDate);

        $report = [
            'type' => 'overall',
            'period' => $this->formatPeriod($startDate, $endDate),
            'summary' => $this->buildOverallReportSummary($reportData),
        ];

        if ($includeDetails) {
            $report['low_quality_records'] = $this->formatLowQualityRecords($reportData['lowQualityRecords']);
            $report['pending_review_records'] = $this->formatPendingReviewRecords($reportData['needingReview']);
        }

        return $report;
    }

    /**
     * 收集整体报告数据
     * @return array{invalidStats: array<array<string, mixed>>, lowQualityRecords: array<EffectiveStudyRecord>, needingReview: array<EffectiveStudyRecord>}
     */
    private function collectOverallReportData(\DateTimeImmutable $startDate, \DateTimeInterface $endDate): array
    {
        return [
            'invalidStats' => $this->recordRepository->getInvalidReasonStats($startDate, $endDate),
            'lowQualityRecords' => $this->recordRepository->findLowQuality(),
            'needingReview' => $this->recordRepository->findNeedingReview(),
        ];
    }

    /**
     * 构建整体报告摘要
     * @param array{invalidStats: array<array<string, mixed>>, lowQualityRecords: array<EffectiveStudyRecord>, needingReview: array<EffectiveStudyRecord>} $reportData
     * @return array<string, mixed>
     */
    private function buildOverallReportSummary(array $reportData): array
    {
        return [
            'low_quality_count' => count($reportData['lowQualityRecords']),
            'pending_review_count' => count($reportData['needingReview']),
            'invalid_reasons' => $this->formatInvalidReasons($reportData['invalidStats']),
        ];
    }

    /**
     * 格式化无效原因
     * @param array<array<string, mixed>> $invalidStats
     * @return array<array<string, mixed>>
     */
    private function formatInvalidReasons(array $invalidStats): array
    {
        return array_map(function ($stat): array {
            $reason = $this->extractInvalidReason($stat);

            return [
                'reason' => $reason,
                'count' => $stat['count'],
                'total_invalid_hours' => round($this->getNumericValue($stat, 'totalInvalidTime') / 3600, 2),
            ];
        }, $invalidStats);
    }

    /**
     * 提取无效原因
     * @param array<string, mixed> $stat
     */
    private function extractInvalidReason(array $stat): string
    {
        if (isset($stat['invalidReason']) && $stat['invalidReason'] instanceof InvalidTimeReason) {
            return $stat['invalidReason']->getLabel();
        }

        return 'Unknown';
    }

    /**
     * 格式化低质量记录
     * @param array<EffectiveStudyRecord> $lowQualityRecords
     * @return array<array<string, mixed>>
     */
    private function formatLowQualityRecords(array $lowQualityRecords): array
    {
        return array_slice(array_map(function ($record): array {
            return [
                'id' => $record->getId(),
                'user_id' => $record->getUserId(),
                'quality_score' => $record->getQualityScore(),
                'date' => $record->getStudyDate()->format('Y-m-d'),
            ];
        }, $lowQualityRecords), 0, 20);
    }

    /**
     * 格式化待审核记录
     * @param array<EffectiveStudyRecord> $needingReview
     * @return array<array<string, mixed>>
     */
    private function formatPendingReviewRecords(array $needingReview): array
    {
        return array_slice(array_map(function ($record): array {
            return [
                'id' => $record->getId(),
                'user_id' => $record->getUserId(),
                'status' => $record->getStatus()->getLabel(),
                'date' => $record->getStudyDate()->format('Y-m-d'),
            ];
        }, $needingReview), 0, 20);
    }

    /**
     * 显示用户报告
     *
     * @param array{type: string, user_id: string, period: array{start: string, end: string}, summary: array<string, mixed>, details?: array<array<string, mixed>>} $report
     */
    private function displayUserReport(array $report, SymfonyStyle $io, string $format, ?string $outputFile): void
    {
        $this->displayUserReportHeader($report, $io);
        $this->displayUserReportSummary($report['summary'], $io);

        if (isset($report['details'])) {
            $this->displayUserReportDetails($report['details'], $io);
        }

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 显示用户报告头部
     * @param array{type: string, user_id: string, period: array{start: string, end: string}, summary: array<string, mixed>, details?: array<array<string, mixed>>} $report
     */
    private function displayUserReportHeader(array $report, SymfonyStyle $io): void
    {
        $io->section("用户 {$report['user_id']} 学时报告 ({$report['period']['start']} 至 {$report['period']['end']})");
    }

    /**
     * 显示用户报告摘要
     * @param array<string, mixed> $summary
     */
    private function displayUserReportSummary(array $summary, SymfonyStyle $io): void
    {
        $summaryRows = $this->buildUserSummaryRows($summary);
        $io->table(['指标', '数值'], $summaryRows);
    }

    /**
     * 构建用户摘要行数据
     * @param array<string, mixed> $summary
     * @return array<array<string>>
     */
    private function buildUserSummaryRows(array $summary): array
    {
        return [
            ['学习记录数', is_scalar($summary['total_records'] ?? 0) ? (string) ($summary['total_records'] ?? 0) : '0'],
            ['总学习时长', $this->formatHours($summary['total_time_hours'] ?? 0)],
            ['有效学习时长', $this->formatHours($summary['effective_time_hours'] ?? 0)],
            ['无效学习时长', $this->formatHours($summary['invalid_time_hours'] ?? 0)],
            ['学习效率', $this->formatPercentage($summary['efficiency_rate'] ?? 0)],
            ['平均质量评分', is_scalar($summary['avg_quality_score'] ?? 0) ? (string) ($summary['avg_quality_score'] ?? 0) : '0'],
            ['平均专注度', is_scalar($summary['avg_focus_score'] ?? 0) ? (string) ($summary['avg_focus_score'] ?? 0) : '0'],
            ['平均交互活跃度', is_scalar($summary['avg_interaction_score'] ?? 0) ? (string) ($summary['avg_interaction_score'] ?? 0) : '0'],
            ['平均学习连续性', is_scalar($summary['avg_continuity_score'] ?? 0) ? (string) ($summary['avg_continuity_score'] ?? 0) : '0'],
        ];
    }

    /**
     * 显示用户报告详情
     * @param array<array<string, mixed>> $details
     */
    private function displayUserReportDetails(array $details, SymfonyStyle $io): void
    {
        $io->section('详细记录');

        $detailRows = $this->buildUserDetailRows($details);
        $io->table(
            ['记录ID', '课程', '课时', '日期', '总时长(分)', '有效时长(分)', '状态', '质量评分'],
            $detailRows
        );
    }

    /**
     * 构建用户详情行数据
     * @param array<array<string, mixed>> $details
     * @return array<array<string>>
     */
    private function buildUserDetailRows(array $details): array
    {
        return array_map(function ($detail): array {
            return [
                $this->formatDetailId($detail['id'] ?? ''),
                $this->formatDetailString($detail['course_title'] ?? ''),
                $this->formatDetailString($detail['lesson_title'] ?? ''),
                $this->formatDetailString($detail['date'] ?? ''),
                $this->formatDetailString($detail['duration_minutes'] ?? ''),
                $this->formatDetailString($detail['effective_minutes'] ?? ''),
                $this->formatDetailString($detail['status'] ?? ''),
                $this->formatDetailString($detail['quality_score'] ?? ''),
            ];
        }, array_slice($details, 0, 20));
    }

    /**
     * 格式化详情ID
     * @param mixed $id
     */
    private function formatDetailId(mixed $id): string
    {
        $idStr = is_scalar($id) ? (string) $id : '';

        return substr($idStr, 0, 8) . '...';
    }

    /**
     * 格式化详情字符串
     * @param mixed $value
     */
    private function formatDetailString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 显示课程报告
     *
     * @param array{type: string, course_id: string, summary: array<string, mixed>, details?: array<array<string, mixed>>} $report
     */
    private function displayCourseReport(array $report, SymfonyStyle $io, string $format, ?string $outputFile): void
    {
        $summary = $report['summary'];

        $io->section("课程 {$report['course_id']} 学时报告");

        $io->table(
            ['指标', '数值'],
            [
                ['学习学员数', $summary['total_students']],
                ['总有效学习时长', $this->formatHours($summary['total_effective_time_hours'] ?? 0)],
                ['平均有效学习时长', $this->formatHours($summary['avg_effective_time_hours'] ?? 0)],
                ['总学习时长', $this->formatHours($summary['total_study_time_hours'] ?? 0)],
                ['平均质量评分', $summary['avg_quality_score']],
            ]
        );

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 显示整体报告
     *
     * @param array{type: string, period: array{start: string, end: string}, summary: array<string, mixed>, low_quality_records?: array<array<string, mixed>>, pending_review_records?: array<array<string, mixed>>} $report
     */
    private function displayOverallReport(array $report, SymfonyStyle $io, string $format, ?string $outputFile): void
    {
        $summary = $report['summary'];

        $io->section("整体学时报告 ({$report['period']['start']} 至 {$report['period']['end']})");

        $io->table(
            ['问题类型', '数量'],
            [
                ['低质量记录', $summary['low_quality_count']],
                ['待审核记录', $summary['pending_review_count']],
            ]
        );

        if ([] !== $summary['invalid_reasons']) {
            $io->section('无效时长原因统计');
            $io->table(
                ['无效原因', '次数', '累计无效时长(小时)'],
                array_map(function ($reason): array {
                    return [
                        $this->getStringValue($reason, 'reason', 'Unknown'),
                        $this->getStringValue($reason, 'count', '0'),
                        $this->getStringValue($reason, 'total_invalid_hours', '0'),
                    ];
                }, $this->getArrayValue($summary, 'invalid_reasons'))
            );
        }

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 输出报告文件
     *
     * @param array<string, mixed> $report
     */
    private function outputReport(array $report, string $format, ?string $outputFile, SymfonyStyle $io): void
    {
        if (null === $outputFile) {
            return;
        }

        $content = match ($format) {
            'json' => false !== json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ? json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : '',
            'csv' => $this->convertToCsv($report),
            default => false !== json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ? json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : '',
        };

        file_put_contents($outputFile, $content);
        $io->note("报告已保存到: {$outputFile}");
    }

    /**
     * 转换为CSV格式
     *
     * @param array<string, mixed> $report
     */
    private function convertToCsv(array $report): string
    {
        // 简化实现，只输出摘要数据
        $csv = '';

        if (isset($report['summary'])) {
            foreach ($this->getArrayValue($report, 'summary') as $key => $value) {
                $valueStr = is_scalar($value) ? (string) $value : '';
                $csv .= "{$key}," . $valueStr . "\n";
            }
        }

        return $csv;
    }

    /**
     * 安全获取数组中的数值
     *
     * @param array<string, mixed> $data
     */
    private function getNumericValue(array $data, string $key): float
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 计算效率比率
     *
     * @param array<string, mixed> $stats
     */
    private function calculateEfficiencyRate(array $stats): float
    {
        $totalTime = $this->getNumericValue($stats, 'totalTime');
        $effectiveTime = $this->getNumericValue($stats, 'effectiveTime');

        if ($totalTime <= 0) {
            return 0.0;
        }

        return round(($effectiveTime / $totalTime) * 100, 1);
    }

    /**
     * 安全获取整数值
     *
     * @param array<string, mixed> $data
     */
    private function getIntValue(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 安全获取字符串值
     *
     * @param mixed $data
     */
    private function getStringValue(mixed $data, string $key, string $default = ''): string
    {
        if (!is_array($data)) {
            return $default;
        }
        $value = $data[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * 安全获取数组值
     *
     * @param mixed $data
     * @param array<mixed> $default
     * @return array<mixed>
     */
    private function getArrayValue(mixed $data, string $key, array $default = []): array
    {
        if (!is_array($data)) {
            return $default;
        }
        $value = $data[$key] ?? $default;

        return is_array($value) ? $value : $default;
    }

    /**
     * 格式化小时数
     */
    private function formatHours(mixed $hours): string
    {
        $value = is_numeric($hours) ? (float) $hours : 0.0;

        return round($value, 2) . ' 小时';
    }

    /**
     * 格式化百分比
     */
    private function formatPercentage(mixed $percentage): string
    {
        $value = is_numeric($percentage) ? (float) $percentage : 0.0;

        return round($value, 1) . '%';
    }
}
