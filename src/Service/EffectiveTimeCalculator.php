<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;

/**
 * 有效学时计算器
 *
 * 负责学时计算和日限制检查
 */
class EffectiveTimeCalculator
{
    private const DEFAULT_DAILY_LIMIT = 8 * 3600;           // 日学时上限（8小时）
    private const CACHE_PREFIX_USER_CONFIG = 'user_study_config_';

    public function __construct(
        private readonly BehaviorDataProcessor $behaviorProcessor,
        private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * 计算有效学习时长
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function calculateEffectiveTime(EffectiveStudyRecord $record, array $behaviorData): float
    {
        $totalDuration = $record->getTotalDuration();
        $effectiveTime = $this->filterValidTimeSegments($totalDuration, $behaviorData);
        $effectiveTime = $this->applyEfficiencyRatios($effectiveTime, $behaviorData);

        return min($effectiveTime, $totalDuration);
    }

    /**
     * 检查日累计限制
     *
     * @return array{valid: bool, reason?: InvalidTimeReason, description?: string}
     */
    public function checkDailyLimit(EffectiveStudyRecord $record): array
    {
        $userId = $record->getUserId();
        $studyDate = $record->getStudyDate();
        $newEffectiveTime = $record->getEffectiveDuration();
        $currentDailyTime = $this->recordRepository->getDailyEffectiveTime($userId, $studyDate);
        $dailyLimit = $this->getUserDailyLimit($userId);
        $totalDailyTime = $currentDailyTime + $newEffectiveTime;

        if ($totalDailyTime <= $dailyLimit) {
            return ['valid' => true];
        }

        return $this->handleDailyLimitExceeded($record, $currentDailyTime, $dailyLimit, $newEffectiveTime);
    }

    /**
     * 过滤有效时间段
     *
     * @param array<int, array<string, mixed>> $behaviorData
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
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function applyEfficiencyRatios(float $effectiveTime, array $behaviorData): float
    {
        $focusRatio = $this->behaviorProcessor->calculateFocusRatio($behaviorData);
        $interactionRatio = $this->behaviorProcessor->calculateInteractionRatio($behaviorData);
        $continuityRatio = $this->behaviorProcessor->calculateContinuityRatio($behaviorData);

        return $effectiveTime * $focusRatio * $interactionRatio * $continuityRatio;
    }

    /**
     * @return array{valid: bool, reason?: InvalidTimeReason, description?: string}
     */
    private function handleDailyLimitExceeded(
        EffectiveStudyRecord $record,
        float $currentDailyTime,
        float $dailyLimit,
        float $newEffectiveTime,
    ): array {
        $exceededTime = ($currentDailyTime + $newEffectiveTime) - $dailyLimit;
        $validTime = max(0, $newEffectiveTime - $exceededTime);

        $this->updateRecordForExceededLimit($record, $validTime);

        if ($validTime <= 0) {
            return $this->createFullyInvalidResult($currentDailyTime, $exceededTime);
        }

        $this->setPartialValidStatus($record, $validTime, $exceededTime);

        return ['valid' => true];
    }

    private function updateRecordForExceededLimit(EffectiveStudyRecord $record, float $validTime): void
    {
        $record->setEffectiveDuration($validTime);
        $record->setInvalidDuration($record->getTotalDuration() - $validTime);
    }

    /**
     * @return array{valid: false, reason: InvalidTimeReason, description: string}
     */
    private function createFullyInvalidResult(float $currentDailyTime, float $exceededTime): array
    {
        return [
            'valid' => false,
            'reason' => InvalidTimeReason::DAILY_LIMIT_EXCEEDED,
            'description' => sprintf(
                '日学时累计超限，当日已学习%.1f小时，超出限制%.1f小时',
                $currentDailyTime / 3600,
                $exceededTime / 3600
            ),
        ];
    }

    private function setPartialValidStatus(EffectiveStudyRecord $record, float $validTime, float $exceededTime): void
    {
        $record->setStatus(StudyTimeStatus::PARTIAL);
        $record->setDescription(sprintf(
            '部分时长超出日限制，有效时长%.1f分钟，无效时长%.1f分钟',
            $validTime / 60,
            $exceededTime / 60
        ));
    }

    /**
     * 获取用户日学时限制
     */
    private function getUserDailyLimit(string $userId): float
    {
        // 从缓存或配置中获取用户的日学时限制
        return $this->cache->get(
            self::CACHE_PREFIX_USER_CONFIG . $userId,
            function () {
                return self::DEFAULT_DAILY_LIMIT;
            }
        );
    }
}
