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
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeService;

#[AsCommand(
    name: 'effective-study-time:report',
    description: '生成有效学时统计报告'
)]
class EffectiveStudyTimeReportCommand extends Command
{
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
                'e',
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user-id');
        $courseId = $input->getOption('course-id');
        $startDate = new \DateTimeImmutable($input->getOption('start-date'));
        $endDate = new \DateTimeImmutable($input->getOption('end-date'));
        $format = $input->getOption('format');
        $outputFile = $input->getOption('output-file');
        $includeDetails = (bool) $input->getOption('include-details');

        $io->title('有效学时统计报告');

        try {
            if ($userId !== null) {
                $report = $this->generateUserReport($userId, $startDate, $endDate, $includeDetails);
                $this->displayUserReport($report, $io, $format, $outputFile);
            } elseif ($courseId !== null) {
                $report = $this->generateCourseReport($courseId, $includeDetails);
                $this->displayCourseReport($report, $io, $format, $outputFile);
            } else {
                $report = $this->generateOverallReport($startDate, $endDate, $includeDetails);
                $this->displayOverallReport($report, $io, $format, $outputFile);
            }

            $io->success('报告生成完成');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('生成学时报告失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('报告生成失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 生成用户报告
     */
    private function generateUserReport(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate, bool $includeDetails): array
    {
        $stats = $this->studyTimeService->getUserStudyTimeStats($userId, $startDate, $endDate);
        
        $report = [
            'type' => 'user',
            'user_id' => $userId,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_records' => (int) $stats['totalRecords'],
                'total_time_hours' => round(($stats['totalTime']) / 3600, 2),
                'effective_time_hours' => round(($stats['effectiveTime']) / 3600, 2),
                'invalid_time_hours' => round(($stats['invalidTime']) / 3600, 2),
                'efficiency_rate' => $stats['totalTime'] > 0 ? round(($stats['effectiveTime'] / $stats['totalTime']) * 100, 1) : 0,
                'avg_quality_score' => round($stats['avgQuality'], 1),
                'avg_focus_score' => round($stats['avgFocus'], 3),
                'avg_interaction_score' => round($stats['avgInteraction'], 3),
                'avg_continuity_score' => round($stats['avgContinuity'], 3),
            ],
        ];

        if ($includeDetails) {
            $records = $this->recordRepository->findByUserAndDate($userId, $startDate);
            $report['details'] = array_map(function($record) {
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

        return $report;
    }

    /**
     * 生成课程报告
     */
    private function generateCourseReport(string $courseId, bool $includeDetails): array
    {
        $stats = $this->studyTimeService->getCourseStudyTimeStats($courseId);
        
        $report = [
            'type' => 'course',
            'course_id' => $courseId,
            'summary' => [
                'total_students' => (int) $stats['totalStudents'],
                'total_effective_time_hours' => round(($stats['totalEffectiveTime']) / 3600, 2),
                'avg_effective_time_hours' => round(($stats['avgEffectiveTime']) / 3600, 2),
                'total_study_time_hours' => round(($stats['totalStudyTime']) / 3600, 2),
                'avg_quality_score' => round($stats['avgQuality'], 1),
            ],
        ];

        if ($includeDetails) {
            $records = $this->recordRepository->findByCourse($courseId, 100);
            $report['details'] = array_map(function($record) {
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
     */
    private function generateOverallReport(\DateTimeInterface $startDate, \DateTimeInterface $endDate, bool $includeDetails): array
    {
        // 无效原因统计
        $invalidStats = $this->recordRepository->getInvalidReasonStats($startDate, $endDate);
        
        // 低质量记录
        $lowQualityRecords = $this->recordRepository->findLowQuality();
        
        // 需要审核的记录
        $needingReview = $this->recordRepository->findNeedingReview();

        $report = [
            'type' => 'overall',
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'low_quality_count' => count($lowQualityRecords),
                'pending_review_count' => count($needingReview),
                'invalid_reasons' => array_map(function($stat) {
                    return [
                        'reason' => $stat['invalidReason']->getLabel(),
                        'count' => (int) $stat['count'],
                        'total_invalid_hours' => round(($stat['totalInvalidTime']) / 3600, 2),
                    ];
                }, $invalidStats),
            ],
        ];

        if ($includeDetails) {
            $report['low_quality_records'] = array_slice(array_map(function($record) {
                return [
                    'id' => $record->getId(),
                    'user_id' => $record->getUserId(),
                    'quality_score' => $record->getQualityScore(),
                    'date' => $record->getStudyDate()->format('Y-m-d'),
                ];
            }, $lowQualityRecords), 0, 20);

            $report['pending_review_records'] = array_slice(array_map(function($record) {
                return [
                    'id' => $record->getId(),
                    'user_id' => $record->getUserId(),
                    'status' => $record->getStatus()->getLabel(),
                    'date' => $record->getStudyDate()->format('Y-m-d'),
                ];
            }, $needingReview), 0, 20);
        }

        return $report;
    }

    /**
     * 显示用户报告
     */
    private function displayUserReport(array $report, SymfonyStyle $io, string $format, ?string $outputFile): void
    {
        $summary = $report['summary'];
        
        $io->section("用户 {$report['user_id']} 学时报告 ({$report['period']['start']} 至 {$report['period']['end']})");
        
        $io->table(
            ['指标', '数值'],
            [
                ['学习记录数', $summary['total_records']],
                ['总学习时长', $summary['total_time_hours'] . ' 小时'],
                ['有效学习时长', $summary['effective_time_hours'] . ' 小时'],
                ['无效学习时长', $summary['invalid_time_hours'] . ' 小时'],
                ['学习效率', $summary['efficiency_rate'] . '%'],
                ['平均质量评分', $summary['avg_quality_score']],
                ['平均专注度', $summary['avg_focus_score']],
                ['平均交互活跃度', $summary['avg_interaction_score']],
                ['平均学习连续性', $summary['avg_continuity_score']],
            ]
        );

        if (isset($report['details'])) {
            $io->section('详细记录');
            $io->table(
                ['记录ID', '课程', '课时', '日期', '总时长(分)', '有效时长(分)', '状态', '质量评分'],
                array_map(function($detail) {
                    return [
                        substr($detail['id'], 0, 8) . '...',
                        $detail['course_title'],
                        $detail['lesson_title'],
                        $detail['date'],
                        $detail['duration_minutes'],
                        $detail['effective_minutes'],
                        $detail['status'],
                        $detail['quality_score'],
                    ];
                }, array_slice($report['details'], 0, 20))
            );
        }

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 显示课程报告
     */
    private function displayCourseReport(array $report, SymfonyStyle $io, string $format, ?string $outputFile): void
    {
        $summary = $report['summary'];
        
        $io->section("课程 {$report['course_id']} 学时报告");
        
        $io->table(
            ['指标', '数值'],
            [
                ['学习学员数', $summary['total_students']],
                ['总有效学习时长', $summary['total_effective_time_hours'] . ' 小时'],
                ['平均有效学习时长', $summary['avg_effective_time_hours'] . ' 小时'],
                ['总学习时长', $summary['total_study_time_hours'] . ' 小时'],
                ['平均质量评分', $summary['avg_quality_score']],
            ]
        );

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 显示整体报告
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

        if (!empty($summary['invalid_reasons'])) {
            $io->section('无效时长原因统计');
            $io->table(
                ['无效原因', '次数', '累计无效时长(小时)'],
                array_map(function($reason) {
                    return [$reason['reason'], $reason['count'], $reason['total_invalid_hours']];
                }, $summary['invalid_reasons'])
            );
        }

        $this->outputReport($report, $format, $outputFile, $io);
    }

    /**
     * 输出报告文件
     */
    private function outputReport(array $report, string $format, ?string $outputFile, SymfonyStyle $io): void
    {
        if ($outputFile === null) {
            return;
        }

        $content = match($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'csv' => $this->convertToCsv($report),
            default => json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        };

        file_put_contents($outputFile, $content);
        $io->note("报告已保存到: {$outputFile}");
    }

    /**
     * 转换为CSV格式
     */
    private function convertToCsv(array $report): string
    {
        // 简化实现，只输出摘要数据
        $csv = '';
        
        if (isset($report['summary'])) {
            foreach ($report['summary'] as $key => $value) {
                $csv .= "{$key},{$value}\n";
            }
        }
        
        return $csv;
    }
} 