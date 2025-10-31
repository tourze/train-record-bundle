<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;

/**
 * 学时验证器
 *
 * 负责学时有效性验证逻辑
 */
class StudyTimeValidator
{
    private const DEFAULT_INTERACTION_TIMEOUT = 300; // 交互超时（5分钟）

    public function __construct(
        private readonly BehaviorDataProcessor $behaviorProcessor,
    ) {
    }

    /**
     * 验证学时有效性
     *
     * @param array<int, array<string, mixed>> $behaviorData
     * @return array{valid: bool, reason?: InvalidTimeReason, description?: string}
     */
    public function validateStudyTime(EffectiveStudyRecord $record, array $behaviorData): array
    {
        // a) 检查是否为浏览信息或测试时间
        if ($this->behaviorProcessor->isBrowsingOrTesting($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::BROWSING_WEB_INFO,
                'description' => '浏览网页信息或在线测试期间不计入有效学时',
            ];
        }

        // b) 检查身份验证
        if ($this->behaviorProcessor->hasAuthenticationFailure($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::IDENTITY_VERIFICATION_FAILED,
                'description' => '身份验证失败后的学习时长',
            ];
        }

        // c) 检查交互间隔
        $interactionCheck = $this->behaviorProcessor->checkInteractionTimeout($behaviorData, self::DEFAULT_INTERACTION_TIMEOUT);
        if (!$interactionCheck['valid']) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::INTERACTION_TIMEOUT,
                'description' => $interactionCheck['description'] ?? 'Interaction timeout detected',
            ];
        }

        // e) 检查是否完成课程测试
        if ($this->isTestRequired($record) && !$this->behaviorProcessor->hasCompletedTest($behaviorData)) {
            return [
                'valid' => false,
                'reason' => InvalidTimeReason::INCOMPLETE_COURSE_TEST,
                'description' => '未完成课程测试的学习时长',
            ];
        }

        return ['valid' => true];
    }

    /**
     * 检查是否需要测试
     */
    public function isTestRequired(EffectiveStudyRecord $record): bool
    {
        // 根据课程或课时配置判断是否需要测试
        // 这里需要根据实际业务逻辑实现
        return false; // 临时返回false
    }
}
