<?php

namespace Tourze\TrainRecordBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习行为服务
 * 
 * 负责记录、分析和检测学习行为，包括防作弊检测
 */
class LearnBehaviorService
{
    // 可疑行为阈值
    private const SUSPICIOUS_THRESHOLDS = [
        'window_blur_count' => 10,      // 窗口失焦次数
        'rapid_seek_count' => 5,        // 快速拖拽次数
        'idle_duration' => 300,         // 空闲时长（秒）
        'mouse_leave_count' => 15,      // 鼠标离开次数
    ];

    public function __construct(
                private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 记录学习行为
     */
    public function recordBehavior(
        string $sessionId,
        string $behaviorType,
        array $behaviorData = []
    ): LearnBehavior {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            throw new \InvalidArgumentException('学习会话不存在');
        }

        $behavior = new LearnBehavior();
        $behavior->setSession($session);
        $behavior->setBehaviorType(BehaviorType::from($behaviorType));
        $behavior->setBehaviorData($behaviorData);
        
        // 设置视频时间戳
        if ((bool) isset($behaviorData['videoTimestamp'])) {
            $behavior->setVideoTimestamp((float) $behaviorData['videoTimestamp']);
        } else {
            $behavior->setVideoTimestamp((float) $session->getCurrentDuration());
        }
        
        // 设置设备信息
        $behavior->setDeviceFingerprint($behaviorData['deviceFingerprint'] ?? 'unknown');
        $behavior->setIpAddress($behaviorData['ipAddress'] ?? '');
        $behavior->setUserAgent($behaviorData['userAgent'] ?? '');
        
        // 检测是否为可疑行为
        $this->detectSuspiciousBehavior($behavior, $session);
        
        $this->entityManager->persist($behavior);
        $this->entityManager->flush();
        
        $this->logger->info('学习行为已记录', [
            'sessionId' => $sessionId,
            'behaviorType' => $behaviorType,
            'isSuspicious' => $behavior->isSuspicious(),
        ]);
        
        return $behavior;
    }

    /**
     * 获取会话的行为统计
     */
    public function getBehaviorStatsBySession(string $sessionId): array
    {
        return $this->behaviorRepository->getBehaviorStatsBySession($sessionId);
    }

    /**
     * 检测可疑行为
     */
    public function detectSuspiciousBehavior(LearnBehavior $behavior, LearnSession $session): void
    {
        $behaviorType = $behavior->getBehaviorType();
        $sessionId = $session->getId();
        
        // 获取会话的历史行为
        $recentBehaviors = $this->behaviorRepository->findRecentBySession($sessionId, 3600); // 最近1小时
        
        $suspiciousReasons = [];
        
        // 检测窗口失焦频率
        if ($behaviorType === 'window_blur') {
            $blurCount = $this->countBehaviorType($recentBehaviors, 'window_blur');
            if ($blurCount >= self::SUSPICIOUS_THRESHOLDS['window_blur_count']) {
                $suspiciousReasons[] = '频繁切换窗口';
            }
        }
        
        // 检测快速拖拽
        if ($behaviorType === 'seek') {
            $seekCount = $this->countBehaviorType($recentBehaviors, 'seek');
            if ($seekCount >= self::SUSPICIOUS_THRESHOLDS['rapid_seek_count']) {
                $suspiciousReasons[] = '频繁拖拽进度';
            }
        }
        
        // 检测鼠标离开频率
        if ($behaviorType === 'mouse_leave') {
            $leaveCount = $this->countBehaviorType($recentBehaviors, 'mouse_leave');
            if ($leaveCount >= self::SUSPICIOUS_THRESHOLDS['mouse_leave_count']) {
                $suspiciousReasons[] = '频繁鼠标离开';
            }
        }
        
        // 检测空闲时间
        if ($behaviorType === 'idle_start') {
            $behaviorData = $behavior->getBehaviorData();
            if ((bool) isset($behaviorData['duration']) && $behaviorData['duration'] > self::SUSPICIOUS_THRESHOLDS['idle_duration']) {
                $suspiciousReasons[] = '长时间无操作';
            }
        }
        
        // 标记可疑行为
        if ((bool) count($suspiciousReasons) > 0) {
            $behavior->setIsSuspicious(true);
            $behavior->setSuspiciousReason(implode(', ', $suspiciousReasons));
        }
    }

    /**
     * 分析行为模式
     */
    public function analyzeBehaviorPattern(
        string $userId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $behaviors = $this->behaviorRepository->findByUserAndDateRange((string) $userId, $startDate, $endDate);
        
        $analysis = [
            'totalBehaviors' => count($behaviors),
            'suspiciousBehaviors' => 0,
            'behaviorTypes' => [],
            'timeDistribution' => [],
            'suspiciousPatterns' => [],
        ];
        
        foreach ($behaviors as $behavior) {
            // 统计行为类型
            $type = $behavior->getBehaviorType();
            $analysis['behaviorTypes'][$type] = ($analysis['behaviorTypes'][$type]) + 1;
            
            // 统计可疑行为
            if ($behavior->isSuspicious()) {
                $analysis['suspiciousBehaviors']++;
                $analysis['suspiciousPatterns'][] = [
                    'type' => $type,
                    'reason' => $behavior->getSuspiciousReason(),
                    'time' => $behavior->getCreateTime()->format('Y-m-d H:i:s'),
                ];
            }
            
            // 时间分布统计
            $hour = $behavior->getCreateTime()->format('H');
            $analysis['timeDistribution'][$hour] = ($analysis['timeDistribution'][$hour]) + 1;
        }
        
        return $analysis;
    }

    /**
     * 生成行为报告
     */
    public function generateBehaviorReport(string $sessionId): array
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            throw new \InvalidArgumentException('学习会话不存在');
        }
        
        $behaviors = $this->behaviorRepository->findBySession($sessionId);
        $suspiciousBehaviors = $this->behaviorRepository->findSuspiciousBySession($sessionId);
        
        return [
            'sessionInfo' => [
                'id' => $session->getId(),
                'lessonTitle' => $session->getLesson()->getTitle(),
                'duration' => $session->getTotalDuration(),
                'startTime' => $session->getFirstLearnTime()?->format('Y-m-d H:i:s'),
                'endTime' => $session->getLastLearnTime()?->format('Y-m-d H:i:s'),
            ],
            'behaviorSummary' => [
                'totalBehaviors' => count($behaviors),
                'suspiciousBehaviors' => count($suspiciousBehaviors),
                'suspiciousRate' => count($behaviors) > 0 ? round(count($suspiciousBehaviors) / count($behaviors) * 100, 2) : 0,
            ],
            'behaviorStats' => $this->getBehaviorStatsBySession($sessionId),
            'suspiciousDetails' => array_map(fn($behavior) => [
                'type' => $behavior->getBehaviorType(),
                'reason' => $behavior->getSuspiciousReason(),
                'time' => $behavior->getCreateTime()->format('Y-m-d H:i:s'),
                'videoTimestamp' => $behavior->getVideoTimestamp(),
                'data' => $behavior->getBehaviorData(),
            ], $suspiciousBehaviors),
            'timeline' => $this->getBehaviorTimeline($sessionId),
        ];
    }

    /**
     * 获取行为时间线
     */
    public function getBehaviorTimeline(string $sessionId): array
    {
        $behaviors = $this->behaviorRepository->findBySession($sessionId);
        
        $timeline = [];
        foreach ($behaviors as $behavior) {
            $timeline[] = [
                'time' => $behavior->getCreateTime()->format('Y-m-d H:i:s'),
                'videoTimestamp' => $behavior->getVideoTimestamp(),
                'type' => $behavior->getBehaviorType(),
                'isSuspicious' => $behavior->isSuspicious(),
                'data' => $behavior->getBehaviorData(),
            ];
        }
        
        // 按时间排序
        usort($timeline, fn($a, $b) => strtotime($a['time']) <=> strtotime($b['time']));
        
        return $timeline;
    }

    /**
     * 统计特定行为类型的数量
     */
    private function countBehaviorType(array $behaviors, string $behaviorType): int
    {
        return count(array_filter($behaviors, fn($behavior) => $behavior->getBehaviorType() === $behaviorType));
    }

    /**
     * 更新会话统计信息
     */
    public function updateSessionStatistics(string $sessionId): array
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            throw new \InvalidArgumentException('学习会话不存在');
        }

        // 获取会话的行为统计
        $behaviorStats = $this->getBehaviorStatsBySession($sessionId);
        
        // 计算可疑行为比例
        $totalBehaviors = $behaviorStats['totalBehaviors'];
        $suspiciousBehaviors = $behaviorStats['suspiciousBehaviors'];
        $suspiciousRate = $totalBehaviors > 0 ? ($suspiciousBehaviors / $totalBehaviors) * 100 : 0;

        $statistics = [
            'sessionId' => $sessionId,
            'totalBehaviors' => $totalBehaviors,
            'suspiciousBehaviors' => $suspiciousBehaviors,
            'suspiciousRate' => round($suspiciousRate, 2),
            'behaviorTypes' => $behaviorStats['behaviorTypes'] ?? [],
            'lastUpdated' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        $this->logger->info('会话统计已更新', [
            'sessionId' => $sessionId,
            'statistics' => $statistics,
        ]);

        return $statistics;
    }
} 