<?php

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnProgress;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习进度服务
 * 
 * 负责管理跨设备的学习进度同步和有效学习时长计算
 */
class LearnProgressService
{
    // 进度计算常量
    private const MIN_SEGMENT_DURATION = 5;     // 最小有效片段时长（秒）
    private const MAX_PROGRESS_JUMP = 30;       // 最大允许进度跳跃（秒）
    private const EFFECTIVE_TIME_RATIO = 0.8;   // 有效时长比例阈值

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnProgressRepository $progressRepository,
        private readonly LearnSessionRepository $sessionRepository,
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
        string $deviceFingerprint = 'unknown'
    ): LearnProgress {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        
        if (!$progress) {
            // 获取课程和课时实体
            $course = $this->entityManager->getRepository(Course::class)->find($courseId);
            $lesson = $this->entityManager->getRepository(Lesson::class)->find($lessonId);
            
            if (!$course || !$lesson) {
                throw new \InvalidArgumentException('课程或课时不存在');
            }
            
            $progress = new LearnProgress();
            $progress->setUserId($userId);
            $progress->setCourse($course);
            $progress->setLesson($lesson);
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
        $progress->setLastUpdateTime(new \DateTime());
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
     */
    public function getUserProgress(string $userId, ?string $courseId = null): array
    {
        if ($courseId) {
            return $this->progressRepository->findByCourse($userId, $courseId);
        }
        
        return $this->progressRepository->findByUser($userId);
    }

    /**
     * 同步设备间的进度
     */
    public function syncProgress(
        string $userId,
        string $lessonId,
        string $fromDevice,
        string $toDevice
    ): ?LearnProgress {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        
        if (!$progress) {
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
     */
    public function calculateCourseProgress(string $userId, string $courseId): array
    {
        $progressList = $this->progressRepository->findByCourse($userId, $courseId);
        
        if (empty($progressList)) {
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
                $completedLessons++;
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
     */
    public function getProgressStatistics(string $userId, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $progressList = $this->progressRepository->findByUserAndDateRange($userId, $startDate, $endDate);
        
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

        if (empty($progressList)) {
            return $statistics;
        }

        $totalProgress = 0.0;

        foreach ($progressList as $progress) {
            if ($progress->isCompleted()) {
                $statistics['completedSessions']++;
            }
            
            $statistics['totalWatchedTime'] += $progress->getWatchedDuration();
            $statistics['totalEffectiveTime'] += $progress->getEffectiveDuration();
            $totalProgress += $progress->getProgress();
            
            // 进度分布统计
            $progressPercent = $progress->getProgress();
            if ($progressPercent <= 25) {
                $statistics['progressDistribution']['0-25%']++;
            } elseif ($progressPercent <= 50) {
                $statistics['progressDistribution']['26-50%']++;
            } elseif ($progressPercent <= 75) {
                $statistics['progressDistribution']['51-75%']++;
            } else {
                $statistics['progressDistribution']['76-100%']++;
            }
        }

        $statistics['averageProgress'] = round($totalProgress / count($progressList), 2);
        $statistics['effectiveTimeRatio'] = $statistics['totalWatchedTime'] > 0 
            ? round(($statistics['totalEffectiveTime'] / $statistics['totalWatchedTime']) * 100, 2) 
            : 0;

        return $statistics;
    }

    /**
     * 更新观看片段
     */
    private function updateWatchedSegments(LearnProgress $progress, float $currentTime): void
    {
        $segments = $progress->getWatchedSegments();
        $lastSegment = end($segments);
        
        if ($lastSegment && abs($currentTime - $lastSegment['end']) <= self::MIN_SEGMENT_DURATION) {
            // 扩展最后一个片段
            $segments[key($segments)]['end'] = $currentTime;
            $segments[key($segments)]['duration'] = $currentTime - $lastSegment['start'];
        } else {
            // 创建新片段
            $segments[] = [
                'start' => $currentTime,
                'end' => $currentTime,
                'duration' => 0,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];
        }
        
        $progress->setWatchedSegments($segments);
        
        // 计算总观看时长
        $totalWatched = array_sum(array_column($segments, 'duration'));
        $progress->setWatchedDuration($totalWatched);
    }

    /**
     * 计算有效学习时长
     */
    private function calculateEffectiveDuration(LearnProgress $progress): void
    {
        $segments = $progress->getWatchedSegments();
        $effectiveTime = 0.0;
        
        foreach ($segments as $segment) {
            // 只计算超过最小时长的片段
            if ($segment['duration'] >= self::MIN_SEGMENT_DURATION) {
                $effectiveTime += $segment['duration'];
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
        string $action = 'update'
    ): void {
        $history = $progress->getProgressHistory();
        
        $history[] = [
            'progress' => $newProgress,
            'device' => $device,
            'action' => $action,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        
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
        
        if (!$progress) {
            return false;
        }

        $progress->setProgress(0.0);
        $progress->setWatchedDuration(0.0);
        $progress->setEffectiveDuration(0.0);
        $progress->setWatchedSegments([]);
        $progress->setIsCompleted(false);
        $progress->setLastUpdateTime(new \DateTime());
        
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
} 