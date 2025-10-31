<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnProgress;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;

/**
 * 学习进度服务
 *
 * 负责管理跨设备的学习进度同步和有效学习时长计算
 */
#[WithMonologChannel(channel: 'train_record')]
class LearnProgressService
{
    // 进度计算常量
    private const MIN_SEGMENT_DURATION = 5;     // 最小有效片段时长（秒）
    private const MAX_PROGRESS_JUMP = 30;       // 最大允许进度跳跃（秒）

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnProgressRepository $progressRepository,
        private readonly EffectiveStudyRecordRepository $effectiveStudyRecordRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 更新学习进度
     */
    public function updateProgress(
        string $userId,
        string $courseId,
        string $lessonId,
        float $currentTime,
        float $totalDuration,
        string $deviceFingerprint = 'unknown',
    ): LearnProgress {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);

        if (null === $progress) {
            $progress = new LearnProgress();
            $progress->setUserId($userId);
            // TODO: setCourseId and setLessonId are deprecated. This service needs to be refactored to use proper Course and Lesson entities
            // $progress->setCourseId($courseId);
            // $progress->setLessonId($lessonId);
            $progress->setProgress(0.0);
            $progress->setWatchedDuration(0.0);
            $progress->setEffectiveDuration(0.0);
            $progress->setWatchedSegments([]);
            $progress->setProgressHistory([]);
            $progress->setIsCompleted(false);
        }

        // 计算新的进度百分比
        $newProgress = $totalDuration > 0 ? ($currentTime / $totalDuration) * 100 : 0;
        $newProgress = min(100, max(0, $newProgress));

        // 检测进度跳跃
        $lastProgress = $progress->getProgress();
        $progressJump = abs($newProgress - $lastProgress);

        if ($progressJump > self::MAX_PROGRESS_JUMP && $lastProgress > 0) {
            $this->logger->warning('检测到进度异常跳跃', [
                'userId' => $userId,
                'lessonId' => $lessonId,
                'lastProgress' => $lastProgress,
                'newProgress' => $newProgress,
                'jump' => $progressJump,
            ]);
        }

        // 更新观看片段
        $this->updateWatchedSegments($progress, $currentTime);

        // 计算有效学习时长
        $this->calculateEffectiveDuration($progress);

        // 更新进度信息
        $progress->setProgress($newProgress);
        $progress->setLastUpdateTime(new \DateTimeImmutable());
        $progress->setLastUpdateDevice($deviceFingerprint);

        // 添加进度历史
        $this->addProgressHistory($progress, $newProgress, $deviceFingerprint);

        // 检查是否完成
        if ($newProgress >= 95.0) { // 95%以上视为完成
            $progress->setIsCompleted(true);
        }

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        $this->logger->info('学习进度已更新', [
            'userId' => $userId,
            'lessonId' => $lessonId,
            'progress' => $newProgress,
            'effectiveDuration' => $progress->getEffectiveDuration(),
        ]);

        return $progress;
    }

    /**
     * 获取用户的学习进度
     *
     * @return array<int, LearnProgress>
     */
    public function getUserProgress(string $userId, ?string $courseId = null): array
    {
        if (null !== $courseId) {
            $result = $this->progressRepository->findByUserAndCourse($userId, $courseId);

            return array_values($result);
        }

        $result = $this->progressRepository->findByUser($userId);

        return array_values($result);
    }

    /**
     * 同步设备间的进度
     */
    public function syncProgress(
        string $userId,
        string $lessonId,
        string $fromDevice,
        string $toDevice,
    ): ?LearnProgress {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);

        if (null === $progress) {
            return null;
        }

        // 更新设备信息
        $progress->setLastUpdateDevice($toDevice);

        // 添加同步记录到历史
        $this->addProgressHistory($progress, $progress->getProgress(), $toDevice, 'sync');

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        $this->logger->info('进度已同步', [
            'userId' => $userId,
            'lessonId' => $lessonId,
            'fromDevice' => $fromDevice,
            'toDevice' => $toDevice,
        ]);

        return $progress;
    }

    /**
     * 计算课程总体进度
     *
     * @return array{totalLessons: int, completedLessons: int, overallProgress: float, totalEffectiveTime: float, averageProgress: float}
     */
    public function calculateCourseProgress(string $userId, string $courseId): array
    {
        $progressList = $this->progressRepository->findByUserAndCourse($userId, $courseId);

        if ([] === $progressList) {
            return [
                'totalLessons' => 0,
                'completedLessons' => 0,
                'overallProgress' => 0.0,
                'totalEffectiveTime' => 0.0,
                'averageProgress' => 0.0,
            ];
        }

        $totalLessons = count($progressList);
        $completedLessons = 0;
        $totalProgress = 0.0;
        $totalEffectiveTime = 0.0;

        foreach ($progressList as $progress) {
            if ($progress->isCompleted()) {
                ++$completedLessons;
            }
            $totalProgress += $progress->getProgress();
            $totalEffectiveTime += $progress->getEffectiveDuration();
        }

        $averageProgress = $totalProgress / $totalLessons;
        $overallProgress = ($completedLessons / $totalLessons) * 100;

        return [
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'overallProgress' => round($overallProgress, 2),
            'totalEffectiveTime' => round($totalEffectiveTime, 2),
            'averageProgress' => round($averageProgress, 2),
        ];
    }

    /**
     * 获取学习统计
     *
     * @return array{totalSessions: int, completedSessions: int, totalWatchedTime: float, totalEffectiveTime: float, averageProgress: float, effectiveTimeRatio: float, progressDistribution: array<string, int>}
     */
    public function getProgressStatistics(string $userId, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $progressList = $this->fetchProgressList($userId, $startDate, $endDate);

        $statistics = [
            'totalSessions' => count($progressList),
            'completedSessions' => 0,
            'totalWatchedTime' => 0.0,
            'totalEffectiveTime' => 0.0,
            'averageProgress' => 0.0,
            'effectiveTimeRatio' => 0.0,
            'progressDistribution' => [
                '0-25%' => 0,
                '26-50%' => 0,
                '51-75%' => 0,
                '76-100%' => 0,
            ],
        ];

        if ([] === $progressList) {
            return $statistics;
        }

        $totalProgress = 0.0;

        foreach ($progressList as $progress) {
            // 更新会话计数器
            if ($progress->isCompleted()) {
                ++$statistics['completedSessions'];
            }

            // 更新时间累计器
            $statistics['totalWatchedTime'] += $progress->getWatchedDuration();
            $statistics['totalEffectiveTime'] += $progress->getEffectiveDuration();

            $totalProgress += $progress->getProgress();
            $statistics['progressDistribution'] = $this->updateProgressDistribution($statistics['progressDistribution'], $progress->getProgress());
        }

        // 最终计算
        $sessionCount = count($progressList);
        $statistics['averageProgress'] = round($totalProgress / $sessionCount, 2);
        $statistics['effectiveTimeRatio'] = $this->calculateEffectiveTimeRatio($statistics);

        return $statistics;
    }

    /**
     * 获取进度列表
     *
     * @return LearnProgress[]
     */
    private function fetchProgressList(string $userId, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): array
    {
        if (null !== $startDate && null !== $endDate) {
            return $this->progressRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        }

        return $this->progressRepository->findByUser($userId);
    }

    /**
     * 计算有效时间比率
     *
     * @param array<string, mixed> $statistics
     */
    private function calculateEffectiveTimeRatio(array $statistics): float
    {
        $totalWatchedTime = $this->getNumericValue($statistics, 'totalWatchedTime');
        if ($totalWatchedTime <= 0) {
            return 0.0;
        }

        $totalEffectiveTime = $this->getNumericValue($statistics, 'totalEffectiveTime');

        return round(($totalEffectiveTime / $totalWatchedTime) * 100, 2);
    }

    /**
     * 更新进度分布统计
     *
     * @param array<string, int> $distribution
     * @return array<string, int>
     */
    private function updateProgressDistribution(array $distribution, float $progressPercent): array
    {
        if ($progressPercent <= 25) {
            ++$distribution['0-25%'];
        } elseif ($progressPercent <= 50) {
            ++$distribution['26-50%'];
        } elseif ($progressPercent <= 75) {
            ++$distribution['51-75%'];
        } else {
            ++$distribution['76-100%'];
        }

        return $distribution;
    }

    /**
     * 更新观看片段
     */
    private function updateWatchedSegments(LearnProgress $progress, float $currentTime): void
    {
        $segments = $progress->getWatchedSegments() ?? [];
        $lastSegment = end($segments);

        if ($this->shouldExtendLastSegment($lastSegment, $currentTime) && is_array($lastSegment)) {
            $segments = $this->extendLastSegment($segments, $lastSegment, $currentTime);
        } else {
            $segments = $this->createNewSegment($segments, $currentTime);
        }

        $progress->setWatchedSegments($segments);
        $this->updateTotalWatchedDuration($progress, $segments);
    }

    /**
     * @param array<string, mixed>|false $lastSegment
     */
    private function shouldExtendLastSegment($lastSegment, float $currentTime): bool
    {
        if (!is_array($lastSegment)) {
            return false;
        }

        if (!isset($lastSegment['end'], $lastSegment['start'])) {
            return false;
        }

        $endTime = is_numeric($lastSegment['end']) ? (float) $lastSegment['end'] : 0.0;

        return abs($currentTime - $endTime) <= self::MIN_SEGMENT_DURATION;
    }

    /**
     * @param array<int, array{start: float, end: float, duration: float, timestamp: string}> $segments
     * @param array<string, mixed> $lastSegment
     * @return array<int, array{start: float, end: float, duration: float, timestamp: string}>
     */
    private function extendLastSegment(array $segments, array $lastSegment, float $currentTime): array
    {
        $lastKey = array_key_last($segments);
        if (null === $lastKey) {
            return $segments;
        }

        $startTime = is_numeric($lastSegment['start']) ? (float) $lastSegment['start'] : 0.0;
        $timestamp = is_string($lastSegment['timestamp'] ?? null) ? $lastSegment['timestamp'] : '';

        $segments[$lastKey] = [
            'start' => $startTime,
            'end' => $currentTime,
            'duration' => $currentTime - $startTime,
            'timestamp' => $timestamp,
        ];

        return $segments;
    }

    /**
     * @param array<int, array{start: float, end: float, duration: float, timestamp: string}> $segments
     * @return array<int, array{start: float, end: float, duration: float, timestamp: string}>
     */
    private function createNewSegment(array $segments, float $currentTime): array
    {
        $segments[] = [
            'start' => $currentTime,
            'end' => $currentTime,
            'duration' => 0.0,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return $segments;
    }

    /**
     * @param array<int, array{start: float, end: float, duration: float, timestamp: string}> $segments
     */
    private function updateTotalWatchedDuration(LearnProgress $progress, array $segments): void
    {
        $totalWatched = 0.0;
        foreach ($segments as $segment) {
            $totalWatched += $segment['duration'];
        }
        $progress->setWatchedDuration($totalWatched);
    }

    /**
     * 计算有效学习时长
     */
    private function calculateEffectiveDuration(LearnProgress $progress): void
    {
        $segments = $progress->getWatchedSegments() ?? [];
        $effectiveTime = 0.0;

        foreach ($segments as $segment) {
            // 只计算超过最小时长的片段
            if ((float) $segment['duration'] >= self::MIN_SEGMENT_DURATION) {
                $effectiveTime += (float) $segment['duration'];
            }
        }

        $progress->setEffectiveDuration($effectiveTime);
    }

    /**
     * 添加进度历史记录
     */
    private function addProgressHistory(
        LearnProgress $progress,
        float $newProgress,
        string $device,
        string $action = 'update',
    ): void {
        $history = $progress->getProgressHistory() ?? [];

        $historyEntry = [
            'time' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'progress' => $newProgress,
            'watchedDuration' => $progress->getWatchedDuration(),
            'device' => $device,
        ];

        $history[] = $historyEntry;

        // 保留最近100条记录
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        $progress->setProgressHistory($history);
    }

    /**
     * 重置学习进度
     */
    public function resetProgress(string $userId, string $lessonId): bool
    {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);

        if (null === $progress) {
            return false;
        }

        $progress->setProgress(0.0);
        $progress->setWatchedDuration(0.0);
        $progress->setEffectiveDuration(0.0);
        $progress->setWatchedSegments([]);
        $progress->setIsCompleted(false);
        $progress->setLastUpdateTime(new \DateTimeImmutable());

        // 添加重置记录
        $this->addProgressHistory($progress, 0.0, 'system', 'reset');

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        $this->logger->info('学习进度已重置', [
            'userId' => $userId,
            'lessonId' => $lessonId,
        ]);

        return true;
    }

    /**
     * 重新计算有效学习时长
     */
    public function recalculateEffectiveTime(string $userId, ?string $courseId = null): int
    {
        $progressRecords = null !== $courseId
            ? $this->progressRepository->findByUserAndCourse($userId, $courseId)
            : $this->progressRepository->findByUser($userId);

        $recalculatedCount = 0;

        foreach ($progressRecords as $progress) {
            // 从有效学时记录中计算总有效时长
            $effectiveRecords = $this->effectiveStudyRecordRepository->findBy([
                'userId' => $userId,
                'lesson' => $progress->getLesson(),
                'status' => StudyTimeStatus::VALID,
            ]);

            $totalEffectiveTime = 0;
            foreach ($effectiveRecords as $record) {
                $totalEffectiveTime += $record->getEffectiveDuration();
            }

            if ($progress->getEffectiveDuration() !== $totalEffectiveTime) {
                $progress->setEffectiveDuration($totalEffectiveTime);
                $this->entityManager->persist($progress);
                ++$recalculatedCount;
            }
        }

        if ($recalculatedCount > 0) {
            $this->entityManager->flush();
        }

        $this->logger->info('有效学习时长重新计算完成', [
            'userId' => $userId,
            'courseId' => $courseId,
            'recalculatedCount' => $recalculatedCount,
        ]);

        return $recalculatedCount;
    }

    /**
     * 安全获取数值
     *
     * @param array<string, mixed> $data
     */
    private function getNumericValue(array $data, string $key): float
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
