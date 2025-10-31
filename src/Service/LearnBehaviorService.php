<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习行为服务
 *
 * 负责记录、分析和检测学习行为，包括防作弊检测
 */
#[WithMonologChannel(channel: 'train_record')]
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
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 记录学习行为
     */
    /**
     * @param array<string, mixed> $behaviorData
     */
    public function recordBehavior(
        string $sessionId,
        string $behaviorType,
        array $behaviorData = [],
    ): LearnBehavior {
        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            throw new ArgumentException('学习会话不存在');
        }

        $behavior = new LearnBehavior();
        $behavior->setSession($session);
        $behavior->setBehaviorType(BehaviorType::from($behaviorType));
        $behavior->setBehaviorData($behaviorData);

        // 设置视频时间戳
        if (isset($behaviorData['videoTimestamp'])) {
            $videoTimestamp = $behaviorData['videoTimestamp'];
            $behavior->setVideoTimestamp(is_scalar($videoTimestamp) ? (string) $videoTimestamp : '');
        } else {
            $behavior->setVideoTimestamp((string) $session->getCurrentDuration());
        }

        // 设置设备信息
        $deviceFingerprint = $behaviorData['deviceFingerprint'] ?? 'unknown';
        $ipAddress = $behaviorData['ipAddress'] ?? '';
        $userAgent = $behaviorData['userAgent'] ?? '';

        $behavior->setDeviceFingerprint(is_string($deviceFingerprint) ? $deviceFingerprint : 'unknown');
        $behavior->setIpAddress(is_string($ipAddress) ? $ipAddress : '');
        $behavior->setUserAgent(is_string($userAgent) ? $userAgent : '');

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
     * @return array<array<string, mixed>>
     */
    public function getBehaviorStatsBySession(string $sessionId): array
    {
        return $this->behaviorRepository->getBehaviorStatsBySession($sessionId);
    }

    public function detectSuspiciousBehavior(LearnBehavior $behavior, LearnSession $session): void
    {
        $behaviorType = $behavior->getBehaviorType();
        $sessionId = $session->getId();
        if (null === $sessionId) {
            return;
        }
        $recentBehaviors = $this->behaviorRepository->findRecentBySession($sessionId, 3600);

        $suspiciousReasons = $this->analyzeSuspiciousBehaviors($behavior, $behaviorType, $recentBehaviors);

        if ([] !== $suspiciousReasons) {
            $this->markAsSuspicious($behavior, $suspiciousReasons);
        }
    }

    /**
     * @param LearnBehavior[] $recentBehaviors
     * @return string[]
     */
    private function analyzeSuspiciousBehaviors(LearnBehavior $behavior, BehaviorType $behaviorType, array $recentBehaviors): array
    {
        $suspiciousReasons = [];

        $suspiciousReasons = array_merge($suspiciousReasons, $this->checkWindowBlurSuspicion($behaviorType, $recentBehaviors));
        $suspiciousReasons = array_merge($suspiciousReasons, $this->checkSeekSuspicion($behaviorType, $recentBehaviors));
        $suspiciousReasons = array_merge($suspiciousReasons, $this->checkMouseLeaveSuspicion($behaviorType, $recentBehaviors));

        return array_merge($suspiciousReasons, $this->checkIdleSuspicion($behavior, $behaviorType));
    }

    /**
     * @param LearnBehavior[] $recentBehaviors
     * @return string[]
     */
    private function checkWindowBlurSuspicion(BehaviorType $behaviorType, array $recentBehaviors): array
    {
        if (BehaviorType::WINDOW_BLUR !== $behaviorType) {
            return [];
        }

        $blurCount = $this->countBehaviorType($recentBehaviors, BehaviorType::WINDOW_BLUR->value);

        return $blurCount >= self::SUSPICIOUS_THRESHOLDS['window_blur_count'] ? ['频繁切换窗口'] : [];
    }

    /**
     * @param LearnBehavior[] $recentBehaviors
     * @return string[]
     */
    private function checkSeekSuspicion(BehaviorType $behaviorType, array $recentBehaviors): array
    {
        if (BehaviorType::SEEK !== $behaviorType) {
            return [];
        }

        $seekCount = $this->countBehaviorType($recentBehaviors, BehaviorType::SEEK->value);

        return $seekCount >= self::SUSPICIOUS_THRESHOLDS['rapid_seek_count'] ? ['频繁拖拽进度'] : [];
    }

    /**
     * @param LearnBehavior[] $recentBehaviors
     * @return string[]
     */
    private function checkMouseLeaveSuspicion(BehaviorType $behaviorType, array $recentBehaviors): array
    {
        if (BehaviorType::MOUSE_LEAVE !== $behaviorType) {
            return [];
        }

        $leaveCount = $this->countBehaviorType($recentBehaviors, BehaviorType::MOUSE_LEAVE->value);

        return $leaveCount >= self::SUSPICIOUS_THRESHOLDS['mouse_leave_count'] ? ['频繁鼠标离开'] : [];
    }

    /**
     * @return string[]
     */
    private function checkIdleSuspicion(LearnBehavior $behavior, BehaviorType $behaviorType): array
    {
        if (BehaviorType::IDLE_START !== $behaviorType) {
            return [];
        }

        $behaviorData = $behavior->getBehaviorData();

        if (isset($behaviorData['duration']) && $behaviorData['duration'] > self::SUSPICIOUS_THRESHOLDS['idle_duration']) {
            return ['长时间无操作'];
        }

        return [];
    }

    /**
     * @param string[] $suspiciousReasons
     */
    private function markAsSuspicious(LearnBehavior $behavior, array $suspiciousReasons): void
    {
        $behavior->setIsSuspicious(true);
        $behavior->setSuspiciousReason(implode(', ', $suspiciousReasons));
    }

    /**
     * 分析行为模式
     * @return array<string, mixed>
     */
    public function analyzeBehaviorPattern(
        string $userId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $behaviors = $this->behaviorRepository->findByUserAndDateRange($userId, $startDate, $endDate);

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
            ++$analysis['behaviorTypes'][$type->value];

            // 统计可疑行为
            if ($behavior->isSuspicious()) {
                ++$analysis['suspiciousBehaviors'];
                $analysis['suspiciousPatterns'][] = [
                    'type' => $type,
                    'reason' => $behavior->getSuspiciousReason(),
                    'time' => $behavior->getCreateTime()?->format('Y-m-d H:i:s') ?? 'unknown',
                ];
            }

            // 时间分布统计
            $createTime = $behavior->getCreateTime();
            if (null !== $createTime) {
                $hour = $createTime->format('H');
                ++$analysis['timeDistribution'][$hour];
            }
        }

        return $analysis;
    }

    /**
     * 生成行为报告
     * @return array<string, mixed>
     */
    public function generateBehaviorReport(string $sessionId): array
    {
        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            throw new ArgumentException('学习会话不存在');
        }

        $behaviors = $this->behaviorRepository->findBySession($sessionId);
        $suspiciousBehaviors = $this->behaviorRepository->findSuspiciousBySession($sessionId);

        return [
            'sessionInfo' => [
                'id' => $session->getId(),
                'lessonTitle' => $session->getLesson()->getTitle(),
                'duration' => $session->getTotalDuration(),
                'startTime' => $session->getFirstLearnTime()?->format('Y-m-d H:i:s') ?? null,
                'endTime' => $session->getLastLearnTime()?->format('Y-m-d H:i:s') ?? null,
            ],
            'behaviorSummary' => [
                'totalBehaviors' => count($behaviors),
                'suspiciousBehaviors' => count($suspiciousBehaviors),
                'suspiciousRate' => count($behaviors) > 0 ? round(count($suspiciousBehaviors) / count($behaviors) * 100, 2) : 0,
            ],
            'behaviorStats' => $this->getBehaviorStatsBySession($sessionId),
            'suspiciousDetails' => array_map(fn ($behavior) => [
                'type' => $behavior->getBehaviorType(),
                'reason' => $behavior->getSuspiciousReason(),
                'time' => $behavior->getCreateTime()?->format('Y-m-d H:i:s') ?? 'unknown',
                'videoTimestamp' => $behavior->getVideoTimestamp(),
                'data' => $behavior->getBehaviorData(),
            ], $suspiciousBehaviors),
            'timeline' => $this->getBehaviorTimeline($sessionId),
        ];
    }

    /**
     * 获取行为时间线
     * @return array<int, array<string, mixed>>
     */
    public function getBehaviorTimeline(string $sessionId): array
    {
        $behaviors = $this->behaviorRepository->findBySession($sessionId);

        $timeline = [];
        foreach ($behaviors as $behavior) {
            $timeline[] = [
                'time' => $behavior->getCreateTime()?->format('Y-m-d H:i:s') ?? 'unknown',
                'videoTimestamp' => $behavior->getVideoTimestamp(),
                'type' => $behavior->getBehaviorType(),
                'isSuspicious' => $behavior->isSuspicious(),
                'data' => $behavior->getBehaviorData(),
            ];
        }

        // 按时间排序
        usort($timeline, fn ($a, $b) => strtotime($a['time']) <=> strtotime($b['time']));

        return $timeline;
    }

    /**
     * 统计特定行为类型的数量
     * @param LearnBehavior[] $behaviors
     */
    private function countBehaviorType(array $behaviors, string $behaviorType): int
    {
        return count(array_filter($behaviors, fn ($behavior) => $behavior->getBehaviorType()->value === $behaviorType));
    }

    /**
     * 更新会话统计信息
     * @return array<string, mixed>
     */
    public function updateSessionStatistics(string $sessionId): array
    {
        $session = $this->sessionRepository->find($sessionId);
        if (null === $session) {
            throw new ArgumentException('学习会话不存在');
        }

        // 获取会话的行为统计
        $behaviorStats = $this->getBehaviorStatsBySession($sessionId);

        // 计算可疑行为比例
        $totalBehaviors = 0;
        $suspiciousBehaviors = 0;

        foreach ($behaviorStats as $stat) {
            $count = $this->getIntValue($stat, 'count');
            $totalBehaviors += $count;

            // 如果是可疑行为类型，累计到可疑计数
            if (isset($stat['behaviorType']) && $this->isSuspiciousBehaviorType($this->getStringValue($stat, 'behaviorType'))) {
                $suspiciousBehaviors += $count;
            }
        }
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

    /**
     * 判断是否为可疑行为类型
     */
    private function isSuspiciousBehaviorType(string $behaviorType): bool
    {
        // 定义可疑行为类型
        $suspiciousTypes = [
            'copy_paste_frequently',
            'window_switch_frequently',
            'idle_time_excessive',
            'learning_time_insufficient',
            'suspicious_operation',
        ];

        return in_array($behaviorType, $suspiciousTypes, true);
    }

    /**
     * 安全获取整数值
     *
     * @param mixed $data
     */
    private function getIntValue(mixed $data, string $key): int
    {
        if (!is_array($data)) {
            return 0;
        }
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
}
