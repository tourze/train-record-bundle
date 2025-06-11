<?php

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 有效学时管理服务
 * 
 * 负责学时认定的核心逻辑：
 * 1. 有效学时计算和验证
 * 2. 无效时长识别和分类
 * 3. 学时状态管理和转换
 * 4. 日累计限制控制
 * 5. 学时质量评估
 */
class EffectiveStudyTimeService
{
    // 默认配置常量
    private const DEFAULT_DAILY_LIMIT = 8 * 3600;           // 日学时上限（8小时）
    private const DEFAULT_INTERACTION_TIMEOUT = 300;       // 交互超时（5分钟）
    private const DEFAULT_MIN_SEGMENT_DURATION = 60;       // 最小有效时段（1分钟）
    private const DEFAULT_QUALITY_THRESHOLD = 6.0;         // 质量评分阈值
    private const DEFAULT_FOCUS_THRESHOLD = 0.7;           // 专注度阈值
    
    // 缓存键前缀
    private const CACHE_PREFIX_DAILY_TIME = 'daily_study_time_';
    private const CACHE_PREFIX_USER_CONFIG = 'user_study_config_';
    
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 处理学时认定
     * 
     * @param LearnSession $session 学习会话
     * @param \DateTimeInterface $startTime 开始时间
     * @param \DateTimeInterface $endTime 结束时间
     * @param float $totalDuration 总时长（秒）
     * @param array $behaviorData 行为数据
     * @return EffectiveStudyRecord 有效学时记录
     */
    public function processStudyTime(
        LearnSession $session,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        float $totalDuration,
        array $behaviorData = []
    ): EffectiveStudyRecord {
        // 创建有效学时记录
        $record = new EffectiveStudyRecord();
        $record->setUserId($session->getStudent()->getId());
        $record->setSession($session);
        $record->setCourse($session->getCourse());
        $record->setLesson($session->getLesson());
        $record->setStudyDate(\DateTime::createFromInterface($startTime)->setTime(0, 0, 0));
        $record->setStartTime($startTime);
        $record->setEndTime($endTime);
        $record->setTotalDuration($totalDuration);
        $record->setBehaviorStats($behaviorData);

        // 初步验证
        $validation = $this->validateStudyTime($record, $behaviorData);
        
        if ($validation['valid']) {
            // 计算有效时长
            $effectiveDuration = $this->calculateEffectiveTime($record, $behaviorData);
            $record->setEffectiveDuration($effectiveDuration);
            $record->setInvalidDuration($totalDuration - $effectiveDuration);
            
            // 检查日累计限制
            $dailyCheck = $this->checkDailyLimit($record);
            if (!$dailyCheck['valid']) {
                $record->setStatus(StudyTimeStatus::INVALID);
                $record->setInvalidReason($dailyCheck['reason']);
                $record->setDescription($dailyCheck['description']);
            } else {
                $record->setStatus(StudyTimeStatus::VALID);
                
                // 计算质量评分
                $this->calculateQualityScores($record, $behaviorData);
                
                // 根据质量决定是否需要审核
                if ($this->needsQualityReview($record)) {
                    $record->setStatus(StudyTimeStatus::PENDING);
                }
            }
        } else {
            // 设置为无效
            $record->setEffectiveDuration(0);
            $record->setInvalidDuration($totalDuration);
            $record->setStatus(StudyTimeStatus::INVALID);
            $record->setInvalidReason($validation['reason']);
            $record->setDescription($validation['description']);
        }

        // 添加证据数据
        $this->addEvidenceData($record, $behaviorData);

        // 保存记录
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        $this->logger->info('学时认定处理完成', [
            'record_id' => $record->getId(),
            'user_id' => $record->getUserId(),
            'status' => $record->getStatus()->value,
            'effective_duration' => $record->getEffectiveDuration(),
            'total_duration' => $record->getTotalDuration(),
        ]);

        return $record;
    }

    /**
     * 验证学时有效性
     */
    private function validateStudyTime(EffectiveStudyRecord $record, array $behaviorData): array
    {
        // a) 检查是否为浏览信息或测试时间
        if ($this->isBrowsingOrTesting($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::BROWSING_WEB_INFO,
                'description' => '浏览网页信息或在线测试期间不计入有效学时'
            ];
        }

        // b) 检查身份验证
        if ($this->hasAuthenticationFailure($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::IDENTITY_VERIFICATION_FAILED,
                'description' => '身份验证失败后的学习时长'
            ];
        }

        // c) 检查交互间隔
        $interactionCheck = $this->checkInteractionTimeout($behaviorData);
        if (!$interactionCheck['valid']) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::INTERACTION_TIMEOUT,
                'description' => $interactionCheck['description']
            ];
        }

        // e) 检查是否完成课程测试
        if ($this->isTestRequired($record) && !$this->hasCompletedTest($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::INCOMPLETE_COURSE_TEST,
                'description' => '未完成课程测试的学习时长'
            ];
        }

        return ['valid' => true];
    }

    /**
     * 计算有效学习时长
     */
    private function calculateEffectiveTime(EffectiveStudyRecord $record, array $behaviorData): float
    {
        $totalDuration = $record->getTotalDuration();
        
        // 基础有效时长（排除明显无效时段）
        $effectiveTime = $this->filterValidTimeSegments($totalDuration, $behaviorData);
        
        // 应用专注度系数
        $focusRatio = $this->calculateFocusRatio($behaviorData);
        $effectiveTime *= $focusRatio;
        
        // 应用交互活跃度系数
        $interactionRatio = $this->calculateInteractionRatio($behaviorData);
        $effectiveTime *= $interactionRatio;
        
        // 应用学习连续性系数
        $continuityRatio = $this->calculateContinuityRatio($behaviorData);
        $effectiveTime *= $continuityRatio;
        
        // 确保不超过总时长
        return min($effectiveTime, $totalDuration);
    }

    /**
     * 检查日累计限制
     */
    private function checkDailyLimit(EffectiveStudyRecord $record): array
    {
        $userId = $record->getUserId();
        $studyDate = $record->getStudyDate();
        $newEffectiveTime = $record->getEffectiveDuration();
        
        // 获取当日已有的有效学时
        $currentDailyTime = $this->recordRepository->getDailyEffectiveTime($userId, $studyDate);
        
        // 获取用户日限制配置
        $dailyLimit = $this->getUserDailyLimit($userId);
        
        if (($currentDailyTime + $newEffectiveTime) > $dailyLimit) {
            $exceededTime = ($currentDailyTime + $newEffectiveTime) - $dailyLimit;
            $validTime = max(0, $newEffectiveTime - $exceededTime);
            
            // 更新记录
            $record->setEffectiveDuration($validTime);
            $record->setInvalidDuration($record->getTotalDuration() - $validTime);
            
            if ($validTime <= 0) {
                return [
                    'valid' => false,
                    'reason' => InvalidTimeReason::DAILY_LIMIT_EXCEEDED,
                    'description' => sprintf('日学时累计超限，当日已学习%.1f小时，超出限制%.1f小时',
                        $currentDailyTime / 3600, $exceededTime / 3600)
                ];
            } else {
                // 部分有效
                $record->setStatus(StudyTimeStatus::PARTIAL);
                $record->setDescription(sprintf('部分时长超出日限制，有效时长%.1f分钟，无效时长%.1f分钟',
                    $validTime / 60, $exceededTime / 60));
            }
        }

        return ['valid' => true];
    }

    /**
     * 计算质量评分
     */
    private function calculateQualityScores(EffectiveStudyRecord $record, array $behaviorData): void
    {
        // 学习质量评分（0-10分）
        $qualityScore = $this->calculateQualityScore($behaviorData);
        $record->setQualityScore($qualityScore);
        
        // 专注度评分（0-1）
        $focusScore = $this->calculateFocusRatio($behaviorData);
        $record->setFocusScore($focusScore);
        
        // 交互活跃度评分（0-1）
        $interactionScore = $this->calculateInteractionRatio($behaviorData);
        $record->setInteractionScore($interactionScore);
        
        // 学习连续性评分（0-1）
        $continuityScore = $this->calculateContinuityRatio($behaviorData);
        $record->setContinuityScore($continuityScore);
    }

    /**
     * 检查是否为浏览或测试时间
     */
    private function isBrowsingOrTesting(array $behaviorData): bool
    {
        $browsingActions = ['browse_info', 'view_materials', 'take_test', 'quiz_attempt'];
        
        foreach ($behaviorData as $behavior) {
            if (in_array($behavior['action'] ?? '', $browsingActions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 检查身份验证失败
     */
    private function hasAuthenticationFailure(array $behaviorData): bool
    {
        foreach ($behaviorData as $behavior) {
            if (($behavior['action'] ?? '') === 'auth_failed') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 检查交互超时
     */
    private function checkInteractionTimeout(array $behaviorData): array
    {
        $maxInterval = self::DEFAULT_INTERACTION_TIMEOUT;
        $lastInteractionTime = null;
        
        foreach ($behaviorData as $behavior) {
            $currentTime = $behavior['timestamp'] ?? null;
            if (!$currentTime) continue;
            
            if ($lastInteractionTime) {
                $interval = $currentTime - $lastInteractionTime;
                if ($interval > $maxInterval) {
                    return [
                        'valid' => false,
                        'description' => sprintf('交互间隔超过%d秒', $maxInterval)
                    ];
                }
            }
            
            $lastInteractionTime = $currentTime;
        }
        
        return ['valid' => true];
    }

    /**
     * 检查是否需要测试
     */
    private function isTestRequired(EffectiveStudyRecord $record): bool
    {
        // 根据课程或课时配置判断是否需要测试
        // 这里需要根据实际业务逻辑实现
        return false; // 临时返回false
    }

    /**
     * 检查是否完成测试
     */
    private function hasCompletedTest(array $behaviorData): bool
    {
        foreach ($behaviorData as $behavior) {
            if (($behavior['action'] ?? '') === 'test_completed') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 过滤有效时间段
     */
    private function filterValidTimeSegments(float $totalDuration, array $behaviorData): float
    {
        // 实现时间段过滤逻辑
        // 排除窗口失焦、鼠标离开、快速拖拽等无效时段
        $validDuration = $totalDuration;
        
        // 这里应该根据行为数据分析有效时段
        // 简化实现，返回80%的时长作为基础有效时长
        return $validDuration * 0.8;
    }

    /**
     * 计算专注度比例
     */
    private function calculateFocusRatio(array $behaviorData): float
    {
        $totalTime = 0;
        $focusTime = 0;
        
        foreach ($behaviorData as $behavior) {
            $duration = $behavior['duration'] ?? 0;
            $totalTime += $duration;
            
            if (!in_array($behavior['action'] ?? '', ['window_blur', 'mouse_leave', 'tab_switch'])) {
                $focusTime += $duration;
            }
        }
        
        return $totalTime > 0 ? min(1.0, $focusTime / $totalTime) : 0.0;
    }

    /**
     * 计算交互活跃度比例
     */
    private function calculateInteractionRatio(array $behaviorData): float
    {
        $interactionCount = 0;
        $totalBehaviors = count($behaviorData);
        
        foreach ($behaviorData as $behavior) {
            if (in_array($behavior['action'] ?? '', ['click', 'scroll', 'key_press', 'video_control'])) {
                $interactionCount++;
            }
        }
        
        return $totalBehaviors > 0 ? min(1.0, $interactionCount / $totalBehaviors) : 0.0;
    }

    /**
     * 计算学习连续性比例
     */
    private function calculateContinuityRatio(array $behaviorData): float
    {
        if (empty($behaviorData)) {
            return 0.0;
        }
        
        $gaps = 0;
        $lastTime = null;
        
        foreach ($behaviorData as $behavior) {
            $currentTime = $behavior['timestamp'] ?? null;
            if (!$currentTime) continue;
            
            if ($lastTime && ($currentTime - $lastTime) > 120) { // 2分钟间隔算中断
                $gaps++;
            }
            
            $lastTime = $currentTime;
        }
        
        // 连续性 = 1 - (中断次数 / 总行为数)
        return max(0.0, 1.0 - ($gaps / count($behaviorData)));
    }

    /**
     * 计算质量评分
     */
    private function calculateQualityScore(array $behaviorData): float
    {
        $score = 5.0; // 基础分
        
        // 专注度加分
        $focusRatio = $this->calculateFocusRatio($behaviorData);
        $score += ($focusRatio - 0.5) * 4; // 最多加2分
        
        // 交互活跃度加分
        $interactionRatio = $this->calculateInteractionRatio($behaviorData);
        $score += ($interactionRatio - 0.3) * 2; // 最多加1.4分
        
        // 连续性加分
        $continuityRatio = $this->calculateContinuityRatio($behaviorData);
        $score += ($continuityRatio - 0.5) * 3; // 最多加1.5分
        
        return max(0.0, min(10.0, $score));
    }

    /**
     * 添加证据数据
     */
    private function addEvidenceData(EffectiveStudyRecord $record, array $behaviorData): void
    {
        $evidence = [
            'total_behaviors' => count($behaviorData),
            'unique_actions' => array_unique(array_column($behaviorData, 'action')),
            'timestamp_range' => [
                'start' => min(array_column($behaviorData, 'timestamp')),
                'end' => max(array_column($behaviorData, 'timestamp')),
            ],
            'interaction_frequency' => count($behaviorData) / ($record->getTotalDuration() / 60), // 每分钟交互次数
        ];
        
        $record->setEvidenceData($evidence);
    }

    /**
     * 检查是否需要质量审核
     */
    private function needsQualityReview(EffectiveStudyRecord $record): bool
    {
        $qualityScore = $record->getQualityScore();
        $focusScore = $record->getFocusScore();
        
        // 质量分数过低或专注度过低需要审核
        return $qualityScore < self::DEFAULT_QUALITY_THRESHOLD || 
               $focusScore < self::DEFAULT_FOCUS_THRESHOLD;
    }

    /**
     * 获取用户日学时限制
     */
    private function getUserDailyLimit(string $userId): float
    {
        // 从缓存或配置中获取用户的日学时限制
        return $this->cache->get(
            self::CACHE_PREFIX_USER_CONFIG . $userId,
            function() {
                return self::DEFAULT_DAILY_LIMIT;
            }
        );
    }

    /**
     * 批量处理学时认定
     */
    public function batchProcessStudyTime(array $sessions): array
    {
        $results = [];
        
        foreach ($sessions as $sessionData) {
            try {
                $record = $this->processStudyTime(
                    $sessionData['session'],
                    $sessionData['start_time'],
                    $sessionData['end_time'],
                    $sessionData['duration'],
                    $sessionData['behavior_data'] ?? []
                );
                
                $results[] = [
                    'success' => true,
                    'record' => $record,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'session_data' => $sessionData,
                ];
                
                $this->logger->error('批量学时处理失败', [
                    'session_id' => $sessionData['session']->getId() ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * 重新计算学时记录
     */
    public function recalculateRecord(EffectiveStudyRecord $record): EffectiveStudyRecord
    {
        $behaviorData = $record->getBehaviorStats() ?? [];
        
        // 重新验证和计算
        $validation = $this->validateStudyTime($record, $behaviorData);
        
        if ($validation['valid']) {
            $effectiveDuration = $this->calculateEffectiveTime($record, $behaviorData);
            $record->setEffectiveDuration($effectiveDuration);
            $record->setInvalidDuration($record->getTotalDuration() - $effectiveDuration);
            
            $dailyCheck = $this->checkDailyLimit($record);
            if (!$dailyCheck['valid']) {
                $record->setStatus(StudyTimeStatus::INVALID);
                $record->setInvalidReason($dailyCheck['reason']);
                $record->setDescription($dailyCheck['description']);
            } else {
                $record->setStatus(StudyTimeStatus::VALID);
                $this->calculateQualityScores($record, $behaviorData);
                
                if ($this->needsQualityReview($record)) {
                    $record->setStatus(StudyTimeStatus::PENDING);
                }
            }
        } else {
            $record->setEffectiveDuration(0);
            $record->setInvalidDuration($record->getTotalDuration());
            $record->setStatus(StudyTimeStatus::INVALID);
            $record->setInvalidReason($validation['reason']);
            $record->setDescription($validation['description']);
        }

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * 获取用户学时统计
     */
    public function getUserStudyTimeStats(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->recordRepository->getUserEfficiencyStats($userId, $startDate, $endDate);
    }

    /**
     * 获取课程学时统计
     */
    public function getCourseStudyTimeStats(string $courseId): array
    {
        return $this->recordRepository->getCourseStudyTimeStats($courseId);
    }
} 