<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Monitor;

use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 监控数据收集器
 * 负责收集学习系统相关的监控数据
 */
class MonitorDataCollector
{
    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnAnomalyRepository $anomalyRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnDeviceRepository $deviceRepository,
    ) {
    }

    /**
     * 收集所有监控数据
     * @return array<string, mixed>
     */
    public function collectAllData(): array
    {
        $timeRange = $this->getTimeRange();
        $sessionsData = $this->collectSessionsData($timeRange);
        $anomaliesData = $this->collectAnomaliesData($timeRange);
        $behaviorsData = $this->collectBehaviorsData($timeRange);
        $devicesData = $this->collectDevicesData($timeRange);
        $systemHealth = $this->calculateSystemHealth(
            $sessionsData['active'],
            $anomaliesData['recent'],
            $behaviorsData['suspicious']
        );

        return [
            'timestamp' => $timeRange['now']->format('Y-m-d H:i:s'),
            'sessions' => $sessionsData,
            'anomalies' => $anomaliesData,
            'behaviors' => $behaviorsData,
            'devices' => $devicesData,
            'system' => $systemHealth,
        ];
    }

    /**
     * 获取时间范围
     * @return array{now: \DateTimeImmutable, oneHourAgo: \DateTimeImmutable}
     */
    private function getTimeRange(): array
    {
        $now = new \DateTimeImmutable();
        $oneHourAgo = (clone $now)->sub(new \DateInterval('PT1H'));

        return ['now' => $now, 'oneHourAgo' => $oneHourAgo];
    }

    /**
     * @param array{now: \DateTimeImmutable, oneHourAgo: \DateTimeImmutable} $timeRange
     * @return array{active: array<LearnSession>, recent: array<LearnSession>, activeCount: int, recentCount: int, details: array<string, mixed>}
     */
    private function collectSessionsData(array $timeRange): array
    {
        $activeSessions = $this->sessionRepository->findActiveSessions();
        $recentSessions = $this->sessionRepository->findByDateRange($timeRange['oneHourAgo'], $timeRange['now']);

        return [
            'active' => $activeSessions,
            'recent' => $recentSessions,
            'activeCount' => count($activeSessions),
            'recentCount' => count($recentSessions),
            'details' => $this->getSessionDetails($activeSessions),
        ];
    }

    /**
     * @param array{now: \DateTimeImmutable, oneHourAgo: \DateTimeImmutable} $timeRange
     * @return array{recent: array<LearnAnomaly>, unresolved: array<LearnAnomaly>, recentCount: int, unresolvedCount: int, types: array<string, int>, severity: array<string, int>}
     */
    private function collectAnomaliesData(array $timeRange): array
    {
        $recentAnomalies = $this->anomalyRepository->findByDateRange($timeRange['oneHourAgo'], $timeRange['now']);
        $unresolvedAnomalies = $this->anomalyRepository->findUnresolved();

        return [
            'recent' => $recentAnomalies,
            'unresolved' => $unresolvedAnomalies,
            'recentCount' => count($recentAnomalies),
            'unresolvedCount' => count($unresolvedAnomalies),
            'types' => $this->getAnomalyTypeDistribution($recentAnomalies),
            'severity' => $this->getAnomalySeverityDistribution($recentAnomalies),
        ];
    }

    /**
     * @param array{now: \DateTimeImmutable, oneHourAgo: \DateTimeImmutable} $timeRange
     * @return array{total: array<LearnBehavior>, suspicious: array<LearnBehavior>, totalCount: int, suspiciousCount: int, suspiciousRate: float}
     */
    private function collectBehaviorsData(array $timeRange): array
    {
        $recentBehaviors = $this->behaviorRepository->findByDateRange($timeRange['oneHourAgo'], $timeRange['now']);
        $suspiciousBehaviors = $this->behaviorRepository->findSuspiciousByDateRange($timeRange['oneHourAgo'], $timeRange['now']);
        $totalCount = count($recentBehaviors);
        $suspiciousCount = count($suspiciousBehaviors);

        return [
            'total' => $recentBehaviors,
            'suspicious' => $suspiciousBehaviors,
            'totalCount' => $totalCount,
            'suspiciousCount' => $suspiciousCount,
            'suspiciousRate' => $totalCount > 0 ? round($suspiciousCount / $totalCount * 100, 2) : 0,
        ];
    }

    /**
     * @param array{now: \DateTimeImmutable, oneHourAgo: \DateTimeImmutable} $timeRange
     * @return array{active: array<LearnDevice>, recent: array<LearnDevice>, activeCount: int, recentCount: int, types: array<string, int>}
     */
    private function collectDevicesData(array $timeRange): array
    {
        $activeDevices = $this->deviceRepository->findActive();
        $recentDevices = $this->deviceRepository->findByLastSeenAfter($timeRange['oneHourAgo']);

        return [
            'active' => $activeDevices,
            'recent' => $recentDevices,
            'activeCount' => count($activeDevices),
            'recentCount' => count($recentDevices),
            'types' => $this->getDeviceTypeDistribution($activeDevices),
        ];
    }

    /**
     * 获取会话详情
     * @param array<LearnSession> $sessions
     * @return array<string, mixed>
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
            $userId = $session->getStudent()->getUserIdentifier();
            $courseId = $session->getCourse()->getId();
            $userIdStr = $userId;
            $courseIdStr = (string) $courseId;

            if (!isset($details['byUser'][$userIdStr])) {
                $details['byUser'][$userIdStr] = 0;
            }
            ++$details['byUser'][$userIdStr];

            if (!isset($details['byCourse'][$courseIdStr])) {
                $details['byCourse'][$courseIdStr] = 0;
            }
            ++$details['byCourse'][$courseIdStr];

            $duration = $session->getTotalDuration();
            $totalDuration += (float) $duration;
        }

        if (count($sessions) > 0) {
            $details['avgDuration'] = round($totalDuration / count($sessions), 2);
        }

        return $details;
    }

    /**
     * 获取异常类型分布
     * @param array<LearnAnomaly> $anomalies
     * @return array<string, int>
     */
    private function getAnomalyTypeDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $type = $anomaly->getAnomalyType()->value;
            ++$distribution[$type];
        }

        return $distribution;
    }

    /**
     * 获取异常严重程度分布
     * @param array<LearnAnomaly> $anomalies
     * @return array<string, int>
     */
    private function getAnomalySeverityDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly->getSeverity()->value;
            ++$distribution[$severity];
        }

        return $distribution;
    }

    /**
     * 获取设备类型分布
     * @param array<LearnDevice> $devices
     * @return array<string, int>
     */
    private function getDeviceTypeDistribution(array $devices): array
    {
        $distribution = [];
        foreach ($devices as $device) {
            $type = $device->getDeviceType();
            ++$distribution[$type];
        }

        return $distribution;
    }

    /**
     * 计算系统健康状态
     * @param array<LearnSession> $activeSessions
     * @param array<LearnAnomaly> $recentAnomalies
     * @param array<LearnBehavior> $suspiciousBehaviors
     * @return array{score: int, status: string, issues: array<string>}
     */
    private function calculateSystemHealth(array $activeSessions, array $recentAnomalies, array $suspiciousBehaviors): array
    {
        $score = 100;
        $issues = [];

        $anomalyResult = $this->calculateAnomalyImpact($recentAnomalies, $issues);
        $score -= $anomalyResult['impact'];
        $issues = $anomalyResult['issues'];

        $behaviorResult = $this->calculateBehaviorImpact($suspiciousBehaviors, $issues);
        $score -= $behaviorResult['impact'];
        $issues = $behaviorResult['issues'];

        $sessionResult = $this->calculateSessionImpact($activeSessions, $issues);
        $score -= $sessionResult['impact'];
        $issues = $sessionResult['issues'];

        return [
            'score' => max(0, $score),
            'status' => $this->determineHealthStatus($score),
            'issues' => $issues,
        ];
    }

    /**
     * @param array<LearnAnomaly> $recentAnomalies
     * @param array<string> $issues
     * @return array{impact: int, issues: array<string>}
     */
    private function calculateAnomalyImpact(array $recentAnomalies, array $issues): array
    {
        $anomalyCount = count($recentAnomalies);

        if ($anomalyCount > 20) {
            $issues[] = '异常数量过多';

            return ['impact' => 30, 'issues' => $issues];
        }

        if ($anomalyCount > 10) {
            $issues[] = '异常数量较多';

            return ['impact' => 15, 'issues' => $issues];
        }

        return ['impact' => 0, 'issues' => $issues];
    }

    /**
     * @param array<LearnBehavior> $suspiciousBehaviors
     * @param array<string> $issues
     * @return array{impact: int, issues: array<string>}
     */
    private function calculateBehaviorImpact(array $suspiciousBehaviors, array $issues): array
    {
        $totalBehaviors = count($suspiciousBehaviors) > 0 ? count($suspiciousBehaviors) * 10 : 1; // 估算总行为数
        $suspiciousRate = count($suspiciousBehaviors) / $totalBehaviors * 100;

        if ($suspiciousRate > 50) {
            $issues[] = '可疑行为率过高';

            return ['impact' => 25, 'issues' => $issues];
        }

        if ($suspiciousRate > 30) {
            $issues[] = '可疑行为率较高';

            return ['impact' => 10, 'issues' => $issues];
        }

        return ['impact' => 0, 'issues' => $issues];
    }

    /**
     * @param array<LearnSession> $activeSessions
     * @param array<string> $issues
     * @return array{impact: int, issues: array<string>}
     */
    private function calculateSessionImpact(array $activeSessions, array $issues): array
    {
        $sessionCount = count($activeSessions);

        if ($sessionCount > 1000) {
            $issues[] = '并发会话过多';

            return ['impact' => 10, 'issues' => $issues];
        }

        if (0 === $sessionCount) {
            $issues[] = '无活跃会话';

            return ['impact' => 5, 'issues' => $issues];
        }

        return ['impact' => 0, 'issues' => $issues];
    }

    private function determineHealthStatus(int $score): string
    {
        return match (true) {
            $score >= 80 => 'healthy',
            $score >= 60 => 'warning',
            default => 'critical',
        };
    }
}
