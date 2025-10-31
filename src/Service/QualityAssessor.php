<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;

/**
 * 质量评估器
 *
 * 负责学时质量评分和评估
 */
class QualityAssessor
{
    private const DEFAULT_QUALITY_THRESHOLD = 6.0;         // 质量评分阈值
    private const DEFAULT_FOCUS_THRESHOLD = 0.7;           // 专注度阈值

    public function __construct(
        private readonly BehaviorDataProcessor $behaviorProcessor,
    ) {
    }

    /**
     * 计算并设置质量评分
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function calculateQualityScores(EffectiveStudyRecord $record, array $behaviorData): void
    {
        // 学习质量评分（0-10分）
        $qualityScore = $this->calculateQualityScore($behaviorData);
        $record->setQualityScore($qualityScore);

        // 专注度评分（0-1）
        $focusScore = $this->behaviorProcessor->calculateFocusRatio($behaviorData);
        $record->setFocusScore($focusScore);

        // 交互活跃度评分（0-1）
        $interactionScore = $this->behaviorProcessor->calculateInteractionRatio($behaviorData);
        $record->setInteractionScore($interactionScore);

        // 学习连续性评分（0-1）
        $continuityScore = $this->behaviorProcessor->calculateContinuityRatio($behaviorData);
        $record->setContinuityScore($continuityScore);
    }

    /**
     * 检查是否需要质量审核
     */
    public function needsQualityReview(EffectiveStudyRecord $record): bool
    {
        $qualityScore = $record->getQualityScore();
        $focusScore = $record->getFocusScore();

        // 质量分数过低或专注度过低需要审核
        return $qualityScore < self::DEFAULT_QUALITY_THRESHOLD
               || $focusScore < self::DEFAULT_FOCUS_THRESHOLD;
    }

    /**
     * 计算质量评分
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function calculateQualityScore(array $behaviorData): float
    {
        $score = 5.0; // 基础分

        // 专注度加分
        $focusRatio = $this->behaviorProcessor->calculateFocusRatio($behaviorData);
        $score += ($focusRatio - 0.5) * 4; // 最多加2分

        // 交互活跃度加分
        $interactionRatio = $this->behaviorProcessor->calculateInteractionRatio($behaviorData);
        $score += ($interactionRatio - 0.3) * 2; // 最多加1.4分

        // 连续性加分
        $continuityRatio = $this->behaviorProcessor->calculateContinuityRatio($behaviorData);
        $score += ($continuityRatio - 0.5) * 3; // 最多加1.5分

        return max(0.0, min(10.0, $score));
    }
}
