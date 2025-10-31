<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Archive;

use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 档案数据收集器
 * 负责收集和汇总学习数据
 */
class ArchiveDataCollector
{
    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnAnomalyRepository $anomalyRepository,
    ) {
    }

    /**
     * 收集学习数据
     * @return array<string, mixed>
     */
    public function collectLearningData(string $userId, string $courseId): array
    {
        $sessions = $this->sessionRepository->findByUserAndCourse($userId, $courseId);
        $behaviors = $this->behaviorRepository->findByUserAndCourse($userId, $courseId);
        $anomalies = $this->anomalyRepository->findByUserAndCourse($userId, $courseId);

        return [
            'sessionSummary' => $this->buildSessionSummary($sessions),
            'behaviorSummary' => $this->buildBehaviorSummary($behaviors),
            'anomalySummary' => $this->buildAnomalySummary($anomalies),
            'totalEffectiveTime' => $this->calculateTotalEffectiveTime($sessions),
            'totalSessions' => count($sessions),
            'archiveGeneratedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 构建会话汇总
     * @param array<LearnSession> $sessions
     * @return array<string, mixed>
     */
    private function buildSessionSummary(array $sessions): array
    {
        return [
            'totalSessions' => count($sessions),
            'totalTime' => array_sum(array_map(fn (LearnSession $s): float => (float) $s->getTotalDuration(), $sessions)),
            'completionRate' => $this->calculateCompletionRate($sessions),
            'averageSessionTime' => $this->calculateAverageSessionTime($sessions),
            'firstLearnTime' => $this->getFirstLearnTime($sessions),
            'lastLearnTime' => $this->getLastLearnTime($sessions),
        ];
    }

    /**
     * 构建行为汇总
     * @param array<LearnBehavior> $behaviors
     * @return array<string, mixed>
     */
    private function buildBehaviorSummary(array $behaviors): array
    {
        return [
            'totalBehaviors' => count($behaviors),
            'behaviorStats' => $this->calculateBehaviorStats($behaviors),
            'suspiciousCount' => count(array_filter($behaviors, fn (LearnBehavior $b): bool => $b->isSuspicious())),
            'mostCommonBehavior' => $this->getMostCommonBehavior($behaviors),
        ];
    }

    /**
     * 构建异常汇总
     * @param array<LearnAnomaly> $anomalies
     * @return array<string, mixed>
     */
    private function buildAnomalySummary(array $anomalies): array
    {
        return [
            'totalAnomalies' => count($anomalies),
            'anomalyTypes' => $this->getAnomalyTypeDistribution($anomalies),
            'resolutionStats' => $this->getAnomalyResolutionStats($anomalies),
            'severityDistribution' => $this->getAnomalySeverityDistribution($anomalies),
        ];
    }

    /**
     * 计算平均会话时间
     * @param array<LearnSession> $sessions
     */
    private function calculateAverageSessionTime(array $sessions): float
    {
        if ([] === $sessions) {
            return 0;
        }

        $totalTime = array_sum(array_map(fn (LearnSession $s): float => (float) $s->getTotalDuration(), $sessions));

        return $totalTime / count($sessions);
    }

    /**
     * 获取首次学习时间
     * @param array<LearnSession> $sessions
     */
    private function getFirstLearnTime(array $sessions): ?string
    {
        if ([] === $sessions) {
            return null;
        }

        $firstTimes = array_map(fn (LearnSession $s): ?\DateTimeImmutable => $s->getFirstLearnTime(), $sessions);
        $firstTimes = array_filter($firstTimes, fn (?\DateTimeImmutable $time): bool => null !== $time);

        if ([] === $firstTimes) {
            return null;
        }

        $minTime = min($firstTimes);

        return $minTime->format('Y-m-d H:i:s');
    }

    /**
     * 获取最后学习时间
     * @param array<LearnSession> $sessions
     */
    private function getLastLearnTime(array $sessions): ?string
    {
        if ([] === $sessions) {
            return null;
        }

        $lastTimes = array_map(fn (LearnSession $s): ?\DateTimeImmutable => $s->getLastLearnTime(), $sessions);
        $lastTimes = array_filter($lastTimes, fn (?\DateTimeImmutable $time): bool => null !== $time);

        if ([] === $lastTimes) {
            return null;
        }

        $maxTime = max($lastTimes);

        return $maxTime->format('Y-m-d H:i:s');
    }

    /**
     * 计算完成率
     * @param array<LearnSession> $sessions
     */
    private function calculateCompletionRate(array $sessions): float
    {
        if ([] === $sessions) {
            return 0.0;
        }

        $completedSessions = array_filter($sessions, fn (LearnSession $s): bool => $s->isFinished());

        return (count($completedSessions) / count($sessions)) * 100;
    }

    /**
     * 计算行为统计
     * @param array<LearnBehavior> $behaviors
     * @return array<string, int>
     */
    private function calculateBehaviorStats(array $behaviors): array
    {
        $stats = [];
        foreach ($behaviors as $behavior) {
            $type = $behavior->getBehaviorType()->value;
            $stats[$type] = ($stats[$type] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * 获取最常见行为
     * @param array<LearnBehavior> $behaviors
     */
    private function getMostCommonBehavior(array $behaviors): ?string
    {
        $stats = $this->calculateBehaviorStats($behaviors);
        if ([] === $stats) {
            return null;
        }
        arsort($stats);

        return array_key_first($stats);
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
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * 获取异常解决统计
     * @param array<LearnAnomaly> $anomalies
     * @return array<string, int>
     */
    private function getAnomalyResolutionStats(array $anomalies): array
    {
        $stats = ['resolved' => 0, 'pending' => 0, 'ignored' => 0];
        foreach ($anomalies as $anomaly) {
            $status = $anomaly->getStatus()->value;
            if ('resolved' === $status) {
                ++$stats['resolved'];
            } elseif ('ignored' === $status) {
                ++$stats['ignored'];
            } else {
                ++$stats['pending'];
            }
        }

        return $stats;
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
            $distribution[$severity] = ($distribution[$severity] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * 计算总有效时长
     * @param array<LearnSession> $sessions
     */
    private function calculateTotalEffectiveTime(array $sessions): float
    {
        return array_sum(array_map(fn (LearnSession $s): float => (float) $s->getTotalDuration(), $sessions));
    }
}
