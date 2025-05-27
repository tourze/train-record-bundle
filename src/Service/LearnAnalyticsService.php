<?php

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习分析服务
 * 
 * 负责学习数据的统计分析，生成各种维度的报表和洞察
 */
class LearnAnalyticsService
{
    // 分析配置常量
    private const DEFAULT_TIME_ZONE = 'Asia/Shanghai';
    private const CACHE_TTL = 3600;                    // 缓存时间（秒）
    private const BATCH_SIZE = 1000;                   // 批量处理大小
    private const PERCENTILE_THRESHOLDS = [25, 50, 75, 90, 95]; // 百分位阈值

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnProgressRepository $progressRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnAnomalyRepository $anomalyRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 生成学习统计报告
     */
    public function generateLearningReport(
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $startDate = $startDate ?? (new \DateTime())->modify('-30 days');
        $endDate = $endDate ?? new \DateTime();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
                'days' => $startDate->diff($endDate)->days + 1,
            ],
            'overview' => $this->generateOverviewStats($startDate, $endDate, $userId, $courseId),
            'sessions' => $this->generateSessionStats($startDate, $endDate, $userId, $courseId),
            'progress' => $this->generateProgressStats($startDate, $endDate, $userId, $courseId),
            'behaviors' => $this->generateBehaviorStats($startDate, $endDate, $userId, $courseId),
            'anomalies' => $this->generateAnomalyStats($startDate, $endDate, $userId, $courseId),
            'trends' => $this->generateTrendAnalysis($startDate, $endDate, $userId, $courseId),
            'insights' => $this->generateInsights($startDate, $endDate, $userId, $courseId),
            'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $this->logger->info('学习报告已生成', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'userId' => $userId,
            'courseId' => $courseId,
        ]);

        return $report;
    }

    /**
     * 获取用户学习分析
     */
    public function getUserAnalytics(
        string $userId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $startDate = $startDate ?? (new \DateTime())->modify('-90 days');
        $endDate = $endDate ?? new \DateTime();

        return [
            'userId' => $userId,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'learningProfile' => $this->generateUserLearningProfile($userId, $startDate, $endDate),
            'performanceMetrics' => $this->calculateUserPerformanceMetrics($userId, $startDate, $endDate),
            'behaviorPatterns' => $this->analyzeUserBehaviorPatterns($userId, $startDate, $endDate),
            'progressAnalysis' => $this->analyzeUserProgress($userId, $startDate, $endDate),
            'recommendations' => $this->generateUserRecommendations($userId, $startDate, $endDate),
        ];
    }

    /**
     * 获取课程分析
     */
    public function getCourseAnalytics(
        string $courseId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $startDate = $startDate ?? (new \DateTime())->modify('-30 days');
        $endDate = $endDate ?? new \DateTime();

        return [
            'courseId' => $courseId,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'enrollmentStats' => $this->getCourseEnrollmentStats($courseId, $startDate, $endDate),
            'completionAnalysis' => $this->analyzeCourseCompletion($courseId, $startDate, $endDate),
            'engagementMetrics' => $this->calculateCourseEngagement($courseId, $startDate, $endDate),
            'difficultyAnalysis' => $this->analyzeCoursedifficulty($courseId, $startDate, $endDate),
            'learnerSegmentation' => $this->segmentCourseLearners($courseId, $startDate, $endDate),
        ];
    }

    /**
     * 获取实时统计
     */
    public function getRealTimeStatistics(): array
    {
        $now = new \DateTime();
        $today = (clone $now)->setTime(0, 0, 0);
        $thisHour = (clone $now)->setTime($now->format('H'), 0, 0);

        return [
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'currentOnline' => $this->getCurrentOnlineUsers(),
            'todayStats' => [
                'totalSessions' => $this->sessionRepository->countByDateRange($today, $now),
                'totalUsers' => $this->sessionRepository->countUniqueUsersByDateRange($today, $now),
                'totalTime' => $this->sessionRepository->sumDurationByDateRange($today, $now),
                'completions' => $this->progressRepository->countCompletionsByDateRange($today, $now),
            ],
            'hourlyStats' => [
                'sessions' => $this->sessionRepository->countByDateRange($thisHour, $now),
                'users' => $this->sessionRepository->countUniqueUsersByDateRange($thisHour, $now),
                'time' => $this->sessionRepository->sumDurationByDateRange($thisHour, $now),
            ],
            'systemHealth' => $this->getSystemHealthMetrics(),
        ];
    }

    /**
     * 生成概览统计
     */
    private function generateOverviewStats(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $filters = ['startDate' => $startDate, 'endDate' => $endDate];
        if ($userId) $filters['userId'] = $userId;
        if ($courseId) $filters['courseId'] = $courseId;

        return [
            'totalSessions' => $this->sessionRepository->countByFilters($filters),
            'totalUsers' => $this->sessionRepository->countUniqueUsersByFilters($filters),
            'totalCourses' => $this->sessionRepository->countUniqueCoursesByFilters($filters),
            'totalLearningTime' => $this->sessionRepository->sumDurationByFilters($filters),
            'averageSessionTime' => $this->sessionRepository->avgDurationByFilters($filters),
            'completionRate' => $this->progressRepository->calculateCompletionRateByFilters($filters),
            'activeUsers' => $this->sessionRepository->countActiveUsersByFilters($filters),
            'newUsers' => $this->sessionRepository->countNewUsersByFilters($filters),
        ];
    }

    /**
     * 生成会话统计
     */
    private function generateSessionStats(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $sessions = $this->sessionRepository->findByDateRangeAndFilters($startDate, $endDate, $userId, $courseId);
        
        $durations = array_map(fn($s) => $s->getTotalDuration(), $sessions);
        $dailyStats = $this->groupSessionsByDay($sessions);

        return [
            'totalSessions' => count($sessions),
            'durationStats' => [
                'total' => array_sum($durations),
                'average' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
                'median' => $this->calculateMedian($durations),
                'percentiles' => $this->calculatePercentiles($durations),
                'min' => count($durations) > 0 ? min($durations) : 0,
                'max' => count($durations) > 0 ? max($durations) : 0,
            ],
            'dailyDistribution' => $dailyStats,
            'hourlyDistribution' => $this->groupSessionsByHour($sessions),
            'deviceDistribution' => $this->groupSessionsByDevice($sessions),
            'completionStats' => $this->calculateSessionCompletionStats($sessions),
        ];
    }

    /**
     * 生成进度统计
     */
    private function generateProgressStats(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $progressRecords = $this->progressRepository->findByDateRangeAndFilters($startDate, $endDate, $userId, $courseId);
        
        $progressValues = array_map(fn($p) => $p->getProgress(), $progressRecords);
        $effectiveTimes = array_map(fn($p) => $p->getEffectiveDuration(), $progressRecords);

        return [
            'totalRecords' => count($progressRecords),
            'progressDistribution' => [
                'average' => count($progressValues) > 0 ? array_sum($progressValues) / count($progressValues) : 0,
                'median' => $this->calculateMedian($progressValues),
                'percentiles' => $this->calculatePercentiles($progressValues),
                'ranges' => $this->calculateProgressRanges($progressValues),
            ],
            'effectiveTimeStats' => [
                'total' => array_sum($effectiveTimes),
                'average' => count($effectiveTimes) > 0 ? array_sum($effectiveTimes) / count($effectiveTimes) : 0,
                'median' => $this->calculateMedian($effectiveTimes),
            ],
            'completionAnalysis' => $this->analyzeProgressCompletion($progressRecords),
            'learningVelocity' => $this->calculateLearningVelocity($progressRecords),
        ];
    }

    /**
     * 生成行为统计
     */
    private function generateBehaviorStats(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $behaviors = $this->behaviorRepository->findByDateRangeAndFilters($startDate, $endDate, $userId, $courseId);
        
        return [
            'totalBehaviors' => count($behaviors),
            'typeDistribution' => $this->groupBehaviorsByType($behaviors),
            'suspiciousCount' => count(array_filter($behaviors, fn($b) => $b->isSuspicious())),
            'suspiciousRate' => count($behaviors) > 0 ? (count(array_filter($behaviors, fn($b) => $b->isSuspicious())) / count($behaviors)) * 100 : 0,
            'temporalPatterns' => $this->analyzeBehaviorTemporalPatterns($behaviors),
            'userBehaviorProfiles' => $this->generateUserBehaviorProfiles($behaviors),
        ];
    }

    /**
     * 生成异常统计
     */
    private function generateAnomalyStats(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $anomalies = $this->anomalyRepository->findByDateRangeAndFilters($startDate, $endDate, $userId, $courseId);
        
        return [
            'totalAnomalies' => count($anomalies),
            'typeDistribution' => $this->groupAnomaliesByType($anomalies),
            'severityDistribution' => $this->groupAnomaliesBySeverity($anomalies),
            'statusDistribution' => $this->groupAnomaliesByStatus($anomalies),
            'resolutionStats' => $this->calculateAnomalyResolutionStats($anomalies),
            'trendAnalysis' => $this->analyzeAnomalyTrends($anomalies),
        ];
    }

    /**
     * 生成趋势分析
     */
    private function generateTrendAnalysis(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        return [
            'sessionTrends' => $this->analyzeSessionTrends($startDate, $endDate, $userId, $courseId),
            'progressTrends' => $this->analyzeProgressTrends($startDate, $endDate, $userId, $courseId),
            'engagementTrends' => $this->analyzeEngagementTrends($startDate, $endDate, $userId, $courseId),
            'qualityTrends' => $this->analyzeQualityTrends($startDate, $endDate, $userId, $courseId),
        ];
    }

    /**
     * 生成洞察
     */
    private function generateInsights(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $userId = null,
        ?string $courseId = null
    ): array {
        $insights = [];

        // 学习时间洞察
        $avgSessionTime = $this->sessionRepository->avgDurationByFilters([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userId' => $userId,
            'courseId' => $courseId,
        ]);

        if ($avgSessionTime > 3600) { // 超过1小时
            $insights[] = [
                'type' => 'learning_time',
                'level' => 'positive',
                'message' => '学习会话时长较长，显示出良好的学习专注度',
                'value' => round($avgSessionTime / 60, 1) . '分钟',
            ];
        } elseif ($avgSessionTime < 600) { // 少于10分钟
            $insights[] = [
                'type' => 'learning_time',
                'level' => 'warning',
                'message' => '学习会话时长较短，建议增加单次学习时间',
                'value' => round($avgSessionTime / 60, 1) . '分钟',
            ];
        }

        // 完成率洞察
        $completionRate = $this->progressRepository->calculateCompletionRateByFilters([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userId' => $userId,
            'courseId' => $courseId,
        ]);

        if ($completionRate > 80) {
            $insights[] = [
                'type' => 'completion_rate',
                'level' => 'positive',
                'message' => '课程完成率很高，学习效果良好',
                'value' => round($completionRate, 1) . '%',
            ];
        } elseif ($completionRate < 30) {
            $insights[] = [
                'type' => 'completion_rate',
                'level' => 'critical',
                'message' => '课程完成率较低，需要关注学习障碍',
                'value' => round($completionRate, 1) . '%',
            ];
        }

        return $insights;
    }

    /**
     * 生成用户学习画像
     */
    private function generateUserLearningProfile(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $sessions = $this->sessionRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        $behaviors = $this->behaviorRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        
        return [
            'learningStyle' => $this->identifyLearningStyle($sessions, $behaviors),
            'preferredTime' => $this->identifyPreferredLearningTime($sessions),
            'sessionPattern' => $this->identifySessionPattern($sessions),
            'engagementLevel' => $this->calculateEngagementLevel($sessions, $behaviors),
            'learningPace' => $this->calculateLearningPace($sessions),
            'strengths' => $this->identifyLearningStrengths($sessions, $behaviors),
            'challenges' => $this->identifyLearningChallenges($sessions, $behaviors),
        ];
    }

    /**
     * 计算中位数
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    /**
     * 计算百分位数
     */
    private function calculatePercentiles(array $values): array
    {
        if (empty($values)) {
            return array_fill_keys(self::PERCENTILE_THRESHOLDS, 0);
        }

        sort($values);
        $count = count($values);
        $percentiles = [];

        foreach (self::PERCENTILE_THRESHOLDS as $percentile) {
            $index = ($percentile / 100) * ($count - 1);
            $lower = floor($index);
            $upper = ceil($index);

            if ($lower === $upper) {
                $percentiles[$percentile] = $values[$lower];
            } else {
                $percentiles[$percentile] = $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
            }
        }

        return $percentiles;
    }

    /**
     * 按天分组会话
     */
    private function groupSessionsByDay(array $sessions): array
    {
        $dailyStats = [];
        foreach ($sessions as $session) {
            $date = $session->getFirstLearnTime()->format('Y-m-d');
            if (!isset($dailyStats[$date])) {
                $dailyStats[$date] = ['count' => 0, 'duration' => 0];
            }
            $dailyStats[$date]['count']++;
            $dailyStats[$date]['duration'] += $session->getTotalDuration();
        }
        return $dailyStats;
    }

    /**
     * 按小时分组会话
     */
    private function groupSessionsByHour(array $sessions): array
    {
        $hourlyStats = array_fill(0, 24, ['count' => 0, 'duration' => 0]);
        foreach ($sessions as $session) {
            $hour = (int) $session->getFirstLearnTime()->format('H');
            $hourlyStats[$hour]['count']++;
            $hourlyStats[$hour]['duration'] += $session->getTotalDuration();
        }
        return $hourlyStats;
    }

    /**
     * 按设备分组会话
     */
    private function groupSessionsByDevice(array $sessions): array
    {
        $deviceStats = [];
        foreach ($sessions as $session) {
            $device = $session->getDeviceFingerprint() ?? 'unknown';
            $deviceStats[$device] = ($deviceStats[$device] ?? 0) + 1;
        }
        return $deviceStats;
    }

    /**
     * 计算进度范围分布
     */
    private function calculateProgressRanges(array $progressValues): array
    {
        $ranges = [
            '0-25%' => 0,
            '26-50%' => 0,
            '51-75%' => 0,
            '76-100%' => 0,
        ];

        foreach ($progressValues as $progress) {
            if ($progress <= 25) {
                $ranges['0-25%']++;
            } elseif ($progress <= 50) {
                $ranges['26-50%']++;
            } elseif ($progress <= 75) {
                $ranges['51-75%']++;
            } else {
                $ranges['76-100%']++;
            }
        }

        return $ranges;
    }

    /**
     * 获取当前在线用户数
     */
    private function getCurrentOnlineUsers(): int
    {
        $fiveMinutesAgo = (new \DateTime())->modify('-5 minutes');
        return $this->sessionRepository->countActiveSessionsSince($fiveMinutesAgo);
    }

    /**
     * 获取系统健康指标
     */
    private function getSystemHealthMetrics(): array
    {
        return [
            'avgResponseTime' => 0.5, // 简化实现
            'errorRate' => 0.01,
            'throughput' => 100,
            'availability' => 99.9,
        ];
    }

    // 其他辅助方法的简化实现...
    private function calculateSessionCompletionStats(array $sessions): array { return []; }
    private function analyzeProgressCompletion(array $progressRecords): array { return []; }
    private function calculateLearningVelocity(array $progressRecords): array { return []; }
    private function groupBehaviorsByType(array $behaviors): array { return []; }
    private function analyzeBehaviorTemporalPatterns(array $behaviors): array { return []; }
    private function generateUserBehaviorProfiles(array $behaviors): array { return []; }
    private function groupAnomaliesByType(array $anomalies): array { return []; }
    private function groupAnomaliesBySeverity(array $anomalies): array { return []; }
    private function groupAnomaliesByStatus(array $anomalies): array { return []; }
    private function calculateAnomalyResolutionStats(array $anomalies): array { return []; }
    private function analyzeAnomalyTrends(array $anomalies): array { return []; }
    private function analyzeSessionTrends(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array { return []; }
    private function analyzeProgressTrends(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array { return []; }
    private function analyzeEngagementTrends(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array { return []; }
    private function analyzeQualityTrends(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?string $userId, ?string $courseId): array { return []; }
    private function calculateUserPerformanceMetrics(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function analyzeUserBehaviorPatterns(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function analyzeUserProgress(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function generateUserRecommendations(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function getCourseEnrollmentStats(string $courseId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function analyzeCourseCompletion(string $courseId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function calculateCourseEngagement(string $courseId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function analyzeCoursedifficulty(string $courseId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function segmentCourseLearners(string $courseId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array { return []; }
    private function identifyLearningStyle(array $sessions, array $behaviors): string { return 'visual'; }
    private function identifyPreferredLearningTime(array $sessions): array { return []; }
    private function identifySessionPattern(array $sessions): string { return 'regular'; }
    private function calculateEngagementLevel(array $sessions, array $behaviors): float { return 0.8; }
    private function calculateLearningPace(array $sessions): string { return 'moderate'; }
    private function identifyLearningStrengths(array $sessions, array $behaviors): array { return []; }
    private function identifyLearningChallenges(array $sessions, array $behaviors): array { return []; }
} 