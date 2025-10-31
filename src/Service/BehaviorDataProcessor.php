<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

/**
 * 行为数据处理器
 *
 * 负责行为数据的转换、分析和统计计算
 */
class BehaviorDataProcessor
{
    /**
     * 将行为数据转换为统计数据
     *
     * @param array<int, array<string, mixed>> $behaviorData
     * @return array<string, mixed>|null
     */
    public function convertToStats(array $behaviorData): ?array
    {
        if ([] === $behaviorData) {
            return null;
        }

        $behaviorStats = [];
        foreach ($behaviorData as $behavior) {
            $behaviorStats = array_merge($behaviorStats, $this->extractValidBehaviorKeys($behavior));
        }

        return $behaviorStats;
    }

    /**
     * 将统计数据转换为行为数据格式
     *
     * @param array<string, mixed> $behaviorStats
     * @return array<int, array<string, mixed>>
     */
    public function convertStatsToDataFormat(array $behaviorStats): array
    {
        if ([] === $behaviorStats) {
            return [];
        }

        if ($this->isAlreadyValidFormat($behaviorStats)) {
            return $this->castToValidFormat($behaviorStats);
        }

        return $this->convertStatsToIndexedFormat($behaviorStats);
    }

    /**
     * 计算专注度比例
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function calculateFocusRatio(array $behaviorData): float
    {
        $totalTime = 0.0;
        $focusTime = 0.0;

        foreach ($behaviorData as $behavior) {
            $duration = $this->extractDuration($behavior);
            $totalTime += $duration;

            if ($this->isFocusedAction($behavior)) {
                $focusTime += $duration;
            }
        }

        return $this->calculateRatio($focusTime, $totalTime);
    }

    /**
     * 提取行为持续时间
     *
     * @param array<string, mixed> $behavior
     */
    private function extractDuration(array $behavior): float
    {
        $durationValue = $behavior['duration'] ?? 0;

        return is_numeric($durationValue) ? (float) $durationValue : 0.0;
    }

    /**
     * 检查是否为专注行为
     *
     * @param array<string, mixed> $behavior
     */
    private function isFocusedAction(array $behavior): bool
    {
        $unfocusedActions = ['window_blur', 'mouse_leave', 'tab_switch'];

        return !in_array($behavior['action'] ?? '', $unfocusedActions, true);
    }

    /**
     * 计算比例（确保在0-1之间）
     */
    private function calculateRatio(float $numerator, float $denominator): float
    {
        return $denominator > 0 ? min(1.0, $numerator / $denominator) : 0.0;
    }

    /**
     * 计算交互活跃度比例
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function calculateInteractionRatio(array $behaviorData): float
    {
        $interactionCount = 0;
        $totalBehaviors = count($behaviorData);

        foreach ($behaviorData as $behavior) {
            if ($this->isInteractionAction($behavior)) {
                ++$interactionCount;
            }
        }

        return $this->calculateRatio((float) $interactionCount, (float) $totalBehaviors);
    }

    /**
     * 检查是否为交互行为
     *
     * @param array<string, mixed> $behavior
     */
    private function isInteractionAction(array $behavior): bool
    {
        $interactionActions = ['click', 'scroll', 'key_press', 'video_control'];

        return in_array($behavior['action'] ?? '', $interactionActions, true);
    }

    /**
     * 计算学习连续性比例
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function calculateContinuityRatio(array $behaviorData): float
    {
        if ([] === $behaviorData) {
            return 0.0;
        }

        $gaps = $this->countTimeGaps($behaviorData);

        // 连续性 = 1 - (中断次数 / 总行为数)
        return max(0.0, 1.0 - ($gaps / count($behaviorData)));
    }

    /**
     * 统计时间间隔超过阈值的次数
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function countTimeGaps(array $behaviorData, int $gapThreshold = 120): int
    {
        $gaps = 0;
        $lastTime = null;

        foreach ($behaviorData as $behavior) {
            $currentTime = $this->extractTimestamp($behavior);
            if (null === $currentTime) {
                continue;
            }

            if (null !== $lastTime && ($currentTime - $lastTime) > $gapThreshold) {
                ++$gaps;
            }

            $lastTime = $currentTime;
        }

        return $gaps;
    }

    /**
     * 提取时间戳
     *
     * @param array<string, mixed> $behavior
     */
    private function extractTimestamp(array $behavior): ?int
    {
        $timestamp = $behavior['timestamp'] ?? null;
        if (null === $timestamp || '' === $timestamp) {
            return null;
        }

        return is_numeric($timestamp) ? (int) $timestamp : null;
    }

    /**
     * 检查是否为浏览或测试时间
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function isBrowsingOrTesting(array $behaviorData): bool
    {
        $browsingActions = ['browse_info', 'view_materials', 'take_test', 'quiz_attempt'];

        foreach ($behaviorData as $behavior) {
            if (in_array($behavior['action'] ?? '', $browsingActions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查身份验证失败
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function hasAuthenticationFailure(array $behaviorData): bool
    {
        foreach ($behaviorData as $behavior) {
            if (($behavior['action'] ?? '') === 'auth_failed') {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否完成测试
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function hasCompletedTest(array $behaviorData): bool
    {
        foreach ($behaviorData as $behavior) {
            if (($behavior['action'] ?? '') === 'test_completed') {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查交互超时
     *
     * @param array<int, array<string, mixed>> $behaviorData
     * @return array{valid: bool, description?: string}
     */
    public function checkInteractionTimeout(array $behaviorData, int $maxInterval = 300): array
    {
        $lastInteractionTime = null;

        foreach ($behaviorData as $behavior) {
            $currentTime = $this->extractTimestamp($behavior);
            if (null === $currentTime) {
                continue;
            }

            if ($this->hasIntervalTimeout($lastInteractionTime, $currentTime, $maxInterval)) {
                return $this->buildTimeoutResult($maxInterval);
            }

            $lastInteractionTime = $currentTime;
        }

        return ['valid' => true];
    }

    private function hasIntervalTimeout(?int $lastTime, int $currentTime, int $maxInterval): bool
    {
        if (null === $lastTime) {
            return false;
        }

        $interval = $currentTime - $lastTime;

        return $interval > $maxInterval;
    }

    /**
     * 构建超时检查结果
     *
     * @return array{valid: bool, description: string}
     */
    private function buildTimeoutResult(int $maxInterval): array
    {
        return [
            'valid' => false,
            'description' => sprintf('交互间隔超过%d秒', $maxInterval),
        ];
    }

    /**
     * 构建证据数据
     *
     * @param array<int, array<string, mixed>> $behaviorData
     * @param float $totalDuration
     * @return array<int, array<string, mixed>>
     */
    public function buildEvidenceData(array $behaviorData, float $totalDuration): array
    {
        return [
            [
                'type' => 'behavior_summary',
                'total_behaviors' => count($behaviorData),
                'unique_actions' => $this->extractUniqueActions($behaviorData),
                'timestamp_range' => $this->buildTimestampRange($behaviorData),
                'interaction_frequency' => $this->calculateInteractionFrequency($behaviorData, $totalDuration),
                'timestamp' => time(),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     * @return array<int<0, max>, mixed>
     */
    private function extractUniqueActions(array $behaviorData): array
    {
        return array_unique(array_column($behaviorData, 'action'));
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     * @return array{start: mixed, end: mixed}|null
     */
    private function buildTimestampRange(array $behaviorData): ?array
    {
        $timestamps = array_column($behaviorData, 'timestamp');

        if ([] === $timestamps) {
            return null;
        }

        return [
            'start' => min($timestamps),
            'end' => max($timestamps),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function calculateInteractionFrequency(array $behaviorData, float $totalDuration): float
    {
        if ($totalDuration <= 0) {
            return 0.0;
        }

        return count($behaviorData) / ($totalDuration / 60);
    }

    /**
     * @param array<string, mixed> $behavior
     * @return array<string, mixed>
     */
    private function extractValidBehaviorKeys(array $behavior): array
    {
        return $behavior;
    }

    /**
     * @param array<string, mixed> $behaviorStats
     */
    private function isAlreadyValidFormat(array $behaviorStats): bool
    {
        return $this->hasSequentialIntegerKeys($behaviorStats) && $this->allElementsAreArrays($behaviorStats);
    }

    /**
     * @param array<string, mixed> $behaviorStats
     */
    private function hasSequentialIntegerKeys(array $behaviorStats): bool
    {
        if ([] === $behaviorStats) {
            return true;
        }

        return $this->allKeysAreIntegers($behaviorStats)
            && $this->keysFormSequence($behaviorStats);
    }

    /**
     * @param array<mixed, mixed> $behaviorStats
     */
    private function allKeysAreIntegers(array $behaviorStats): bool
    {
        foreach (array_keys($behaviorStats) as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed, mixed> $behaviorStats
     */
    private function keysFormSequence(array $behaviorStats): bool
    {
        $keys = array_keys($behaviorStats);
        $expectedKeys = range(0, count($behaviorStats) - 1);

        return $keys === $expectedKeys;
    }

    /**
     * @param array<string, mixed> $behaviorStats
     */
    private function allElementsAreArrays(array $behaviorStats): bool
    {
        foreach ($behaviorStats as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $behaviorStats
     * @return array<int, array<string, mixed>>
     */
    private function castToValidFormat(array $behaviorStats): array
    {
        $result = [];
        foreach ($behaviorStats as $index => $item) {
            if (is_array($item) && is_int($index)) {
                /** @var array<string, mixed> $item */
                $result[$index] = $item;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $behaviorStats
     * @return array<int, array<string, mixed>>
     */
    private function convertStatsToIndexedFormat(array $behaviorStats): array
    {
        $behaviorData = [];
        $index = 0;
        foreach ($behaviorStats as $key => $value) {
            $behaviorData[$index] = [
                'type' => 'stat',
                'key' => $key,
                'value' => $value,
                'timestamp' => time(),
            ];
            ++$index;
        }

        return $behaviorData;
    }
}
