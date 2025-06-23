<?php

namespace Tourze\TrainRecordBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;

/**
 * 有效学时通知服务
 * 
 * 负责向学员发送学时认定相关的通知：
 * 1. 实时学时状态提醒
 * 2. 无效学时原因通知
 * 3. 日累计超限提醒
 * 4. 质量评分反馈
 * 5. 学时认定结果通知
 */
class EffectiveStudyTimeNotificationService
{
    // 通知类型常量
    private const NOTIFICATION_REALTIME = 'realtime';
    private const NOTIFICATION_INVALID = 'invalid';
    private const NOTIFICATION_DAILY_LIMIT = 'daily_limit';
    private const NOTIFICATION_QUALITY = 'quality';
    private const NOTIFICATION_RESULT = 'result';
    
    // 缓存键前缀
    private const CACHE_PREFIX_NOTIFICATION = 'study_notification_';
    
    public function __construct(
                private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        // 注意：实际项目中可能需要注入具体的通知服务（如短信、邮件、websocket等）
    ) {
    }

    /**
     * 发送实时学时状态提醒
     */
    public function sendRealtimeStudyStatus(string $userId, array $statusData): bool
    {
        try {
            $message = $this->buildRealtimeMessage($statusData);
            
            // 检查是否需要发送（避免频繁通知）
            if (!$this->shouldSendRealtimeNotification($userId, $statusData)) {
                return true;
            }
            
            $notification = [
                'type' => self::NOTIFICATION_REALTIME,
                'user_id' => $userId,
                'message' => $message,
                'data' => $statusData,
                'timestamp' => time(),
            ];
            
            // 发送通知（这里模拟发送，实际需要对接具体通知渠道）
            $this->sendNotification($notification);
            
            // 更新发送记录缓存
            $this->updateNotificationCache($userId, self::NOTIFICATION_REALTIME);
            
            $this->logger->info('发送实时学时状态提醒', [
                'user_id' => $userId,
                'message' => $message,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logger->error('发送实时学时状态提醒失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 发送无效学时通知
     */
    public function sendInvalidTimeNotification(string $userId, InvalidTimeReason $reason, array $details = []): bool
    {
        try {
            $message = $reason->getNotificationMessage();
            
            $notification = [
                'type' => self::NOTIFICATION_INVALID,
                'user_id' => $userId,
                'title' => '学时状态提醒',
                'message' => $message,
                'reason' => $reason->value,
                'reason_label' => $reason->getLabel(),
                'details' => $details,
                'timestamp' => time(),
                'requires_action' => $reason->requiresStudentNotification(),
            ];
            
            $this->sendNotification($notification);
            
            $this->logger->info('发送无效学时通知', [
                'user_id' => $userId,
                'reason' => $reason->value,
                'message' => $message,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logger->error('发送无效学时通知失败', [
                'user_id' => $userId,
                'reason' => $reason->value,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 发送日累计限制超限通知
     */
    public function sendDailyLimitNotification(string $userId, float $currentTime, float $dailyLimit, float $exceededTime): bool
    {
        try {
            $message = sprintf(
                '您今日的有效学习时长已达到上限（%.1f小时），超出部分（%.1f小时）不计入学时认定。',
                $dailyLimit / 3600,
                $exceededTime / 3600
            );
            
            $notification = [
                'type' => self::NOTIFICATION_DAILY_LIMIT,
                'user_id' => $userId,
                'title' => '日学时限制提醒',
                'message' => $message,
                'data' => [
                    'current_time_hours' => round($currentTime / 3600, 2),
                    'daily_limit_hours' => round($dailyLimit / 3600, 2),
                    'exceeded_time_hours' => round($exceededTime / 3600, 2),
                ],
                'timestamp' => time(),
                'priority' => 'high',
            ];
            
            $this->sendNotification($notification);
            
            $this->logger->warning('发送日累计限制超限通知', [
                'user_id' => $userId,
                'current_time' => $currentTime,
                'daily_limit' => $dailyLimit,
                'exceeded_time' => $exceededTime,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logger->error('发送日累计限制超限通知失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 发送质量评分反馈
     */
    public function sendQualityFeedback(string $userId, float $qualityScore, array $scoreDetails = []): bool
    {
        try {
            $level = $this->getQualityLevel($qualityScore);
            $message = $this->buildQualityMessage($qualityScore, $level);
            
            $notification = [
                'type' => self::NOTIFICATION_QUALITY,
                'user_id' => $userId,
                'title' => '学习质量反馈',
                'message' => $message,
                'data' => [
                    'quality_score' => $qualityScore,
                    'quality_level' => $level,
                    'score_details' => $scoreDetails,
                ],
                'timestamp' => time(),
            ];
            
            $this->sendNotification($notification);
            
            $this->logger->info('发送质量评分反馈', [
                'user_id' => $userId,
                'quality_score' => $qualityScore,
                'level' => $level,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logger->error('发送质量评分反馈失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 发送学时认定结果通知
     */
    public function sendStudyTimeResultNotification(EffectiveStudyRecord $record): bool
    {
        try {
            $userId = $record->getUserId();
            $status = $record->getStatus();
            $message = $this->buildResultMessage($record);
            
            $notification = [
                'type' => self::NOTIFICATION_RESULT,
                'user_id' => $userId,
                'title' => '学时认定结果',
                'message' => $message,
                'data' => [
                    'record_id' => $record->getId(),
                    'status' => $status->value,
                    'status_label' => $status->getLabel(),
                    'course_title' => $record->getCourse()->getTitle(),
                    'lesson_title' => $record->getLesson()->getTitle(),
                    'study_date' => $record->getStudyDate()->format('Y-m-d'),
                    'effective_time_minutes' => round($record->getEffectiveDuration() / 60, 1),
                    'quality_score' => $record->getQualityScore(),
                ],
                'timestamp' => time(),
                'priority' => $status->isFinal() ? 'high' : 'normal',
            ];
            
            $this->sendNotification($notification);
            
            // 标记为已通知
            $record->setStudentNotified(true);
            $this->entityManager->persist($record);
            $this->entityManager->flush();
            
            $this->logger->info('发送学时认定结果通知', [
                'user_id' => $userId,
                'record_id' => $record->getId(),
                'status' => $status->value,
            ]);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logger->error('发送学时认定结果通知失败', [
                'record_id' => $record->getId(),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 批量发送未通知的学时结果
     */
    public function sendPendingNotifications(): int
    {
        $unnotifiedRecords = $this->recordRepository->findUnnotified();
        $sentCount = 0;
        
        foreach ($unnotifiedRecords as $record) {
            if ($this->sendStudyTimeResultNotification($record)) {
                $sentCount++;
            }
        }
        
        $this->logger->info('批量发送学时通知完成', [
            'total_records' => count($unnotifiedRecords),
            'sent_count' => $sentCount,
        ]);
        
        return $sentCount;
    }

    /**
     * 构建实时消息
     */
    private function buildRealtimeMessage(array $statusData): string
    {
        $effectiveTime = $statusData['effective_time'];
        $totalTime = $statusData['total_time'];
        $efficiency = $totalTime > 0 ? round(($effectiveTime / $totalTime) * 100, 1) : 0;
        
        return sprintf(
            '当前有效学习时长：%.1f分钟，学习效率：%.1f%%',
            $effectiveTime / 60,
            $efficiency
        );
    }

    /**
     * 检查是否应该发送实时通知
     */
    private function shouldSendRealtimeNotification(string $userId, array $statusData): bool
    {
        $cacheKey = self::CACHE_PREFIX_NOTIFICATION . 'realtime_' . $userId;
        $lastSent = $this->cache->get($cacheKey, fn() => 0);
        
        // 避免频繁发送，至少间隔5分钟
        if ((bool) (time() - $lastSent) < 300) {
            return false;
        }
        
        return true;
    }

    /**
     * 获取质量等级
     */
    private function getQualityLevel(float $score): string
    {
        return match (true) {
            $score >= 9.0 => '优秀',
            $score >= 8.0 => '良好',
            $score >= 7.0 => '中等',
            $score >= 6.0 => '及格',
            default => '不及格',
        };
    }

    /**
     * 构建质量消息
     */
    private function buildQualityMessage(float $score, string $level): string
    {
        $message = sprintf('本次学习质量评分：%.1f分（%s）', $score, $level);
        
        if ($score < 6.0) {
            $message .= '，建议提高学习专注度和交互活跃度。';
        } elseif ($score >= 8.0) {
            $message .= '，学习状态很好，请继续保持！';
        }
        
        return $message;
    }

    /**
     * 构建结果消息
     */
    private function buildResultMessage(EffectiveStudyRecord $record): string
    {
        $status = $record->getStatus();
        $effectiveMinutes = round($record->getEffectiveDuration() / 60, 1);
        
        $message = sprintf(
            '课程《%s》课时《%s》的学时认定结果：%s，有效学习时长：%.1f分钟',
            $record->getCourse()->getTitle(),
            $record->getLesson()->getTitle(),
            $status->getLabel(),
            $effectiveMinutes
        );
        
        if ($record->getInvalidReason() !== null) {
            $message .= '，无效原因：' . $record->getInvalidReason()->getLabel();
        }
        
        if ($record->getQualityScore() !== null) {
            $message .= sprintf('，质量评分：%.1f分', $record->getQualityScore());
        }
        
        return $message;
    }

    /**
     * 发送通知（抽象方法，实际需要对接具体通知渠道）
     */
    private function sendNotification(array $notification): void
    {
        // 这里应该对接具体的通知渠道，如：
        // - WebSocket 实时推送
        // - 站内消息系统
        // - 短信通知（重要通知）
        // - 邮件通知
        // - 移动端推送
        
        // 示例：记录到日志
        $this->logger->info('发送学时通知', $notification);
        
        // 示例：存储到数据库通知表（如果有的话）
        // $this->notificationRepository->create($notification);
        
        // 示例：发送WebSocket消息
        // $this->websocketService->sendToUser($notification['user_id'], $notification);
    }

    /**
     * 更新通知缓存
     */
    private function updateNotificationCache(string $userId, string $type): void
    {
        $cacheKey = self::CACHE_PREFIX_NOTIFICATION . $type . '_' . $userId;
        $this->cache->get($cacheKey, function() {
            return time();
        });
    }

} 