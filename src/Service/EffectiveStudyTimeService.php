<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;

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
#[WithMonologChannel(channel: 'train_record')]
class EffectiveStudyTimeService
{
    public function __construct(
        private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly BehaviorDataProcessor $behaviorProcessor,
        private readonly StudyTimeValidator $validator,
        private readonly EffectiveTimeCalculator $timeCalculator,
        private readonly QualityAssessor $qualityAssessor,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 处理学时认定
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    public function processStudyTime(
        LearnSession $session,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        float $totalDuration,
        array $behaviorData = [],
    ): EffectiveStudyRecord {
        $record = $this->createStudyRecord($session, $startTime, $endTime, $totalDuration, $behaviorData);
        $this->processStudyValidation($record, $behaviorData);
        $this->saveAndLogRecord($record);

        return $record;
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function createStudyRecord(
        LearnSession $session,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        float $totalDuration,
        array $behaviorData,
    ): EffectiveStudyRecord {
        $record = new EffectiveStudyRecord();
        $this->setBasicRecordInfo($record, $session, $startTime, $endTime, $totalDuration);
        $record->setBehaviorStats($this->behaviorProcessor->convertToStats($behaviorData));

        return $record;
    }

    private function setBasicRecordInfo(
        EffectiveStudyRecord $record,
        LearnSession $session,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        float $totalDuration,
    ): void {
        $record->setUserId($session->getStudent()->getUserIdentifier());
        $record->setSession($session);
        $record->setCourse($session->getCourse());
        $record->setLesson($session->getLesson());
        $record->setStudyDate(\DateTimeImmutable::createFromInterface($startTime)->setTime(0, 0, 0));
        $record->setStartTime(\DateTimeImmutable::createFromInterface($startTime));
        $record->setEndTime(\DateTimeImmutable::createFromInterface($endTime));
        $record->setTotalDuration($totalDuration);
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function processStudyValidation(EffectiveStudyRecord $record, array $behaviorData): void
    {
        $validation = $this->validator->validateStudyTime($record, $behaviorData);

        if ($validation['valid']) {
            $this->processValidStudyTime($record, $behaviorData);
        } else {
            $this->processInvalidStudyTime($record, $validation);
        }

        $this->addEvidenceData($record, $behaviorData);
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function processValidStudyTime(EffectiveStudyRecord $record, array $behaviorData): void
    {
        $effectiveDuration = $this->timeCalculator->calculateEffectiveTime($record, $behaviorData);
        $record->setEffectiveDuration($effectiveDuration);
        $record->setInvalidDuration($record->getTotalDuration() - $effectiveDuration);

        $dailyCheck = $this->timeCalculator->checkDailyLimit($record);
        if (!$dailyCheck['valid']) {
            $this->setInvalidStatus($record, $dailyCheck);
        } else {
            $this->setValidStatusWithQualityCheck($record, $behaviorData);
        }
    }

    /**
     * @param array{valid: bool, reason?: InvalidTimeReason, description?: string} $validation
     */
    private function processInvalidStudyTime(EffectiveStudyRecord $record, array $validation): void
    {
        $record->setEffectiveDuration(0);
        $record->setInvalidDuration($record->getTotalDuration());
        $record->setStatus(StudyTimeStatus::INVALID);
        $record->setInvalidReason($validation['reason'] ?? InvalidTimeReason::IDENTITY_VERIFICATION_FAILED);
        $record->setDescription($validation['description'] ?? 'Unknown reason');
    }

    /**
     * @param array{valid: bool, reason?: InvalidTimeReason, description?: string} $dailyCheck
     */
    private function setInvalidStatus(EffectiveStudyRecord $record, array $dailyCheck): void
    {
        $record->setStatus(StudyTimeStatus::INVALID);
        $record->setInvalidReason($dailyCheck['reason'] ?? InvalidTimeReason::DAILY_LIMIT_EXCEEDED);
        $record->setDescription($dailyCheck['description'] ?? 'Daily limit check failed');
    }

    /**
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function setValidStatusWithQualityCheck(EffectiveStudyRecord $record, array $behaviorData): void
    {
        $record->setStatus(StudyTimeStatus::VALID);
        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        if ($this->qualityAssessor->needsQualityReview($record)) {
            $record->setStatus(StudyTimeStatus::PENDING);
        }
    }

    private function saveAndLogRecord(EffectiveStudyRecord $record): void
    {
        $this->entityManager->persist($record);
        $this->entityManager->flush();

        $this->logger->info('学时认定处理完成', [
            'record_id' => $record->getId(),
            'user_id' => $record->getUserId(),
            'status' => $record->getStatus()->value,
            'effective_duration' => $record->getEffectiveDuration(),
            'total_duration' => $record->getTotalDuration(),
        ]);
    }

    /**
     * 添加证据数据
     *
     * @param array<int, array<string, mixed>> $behaviorData
     */
    private function addEvidenceData(EffectiveStudyRecord $record, array $behaviorData): void
    {
        $evidence = $this->behaviorProcessor->buildEvidenceData($behaviorData, $record->getTotalDuration());
        $record->setEvidenceData($evidence);
    }

    /**
     * 批量处理学时认定
     *
     * @param array<int, array{session: LearnSession, start_time: \DateTimeInterface, end_time: \DateTimeInterface, duration: float, behavior_data?: array<int, array<string, mixed>>}> $sessions
     * @return array<int, array{success: bool, record?: EffectiveStudyRecord, error?: string, session_data?: mixed}>
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
        $behaviorStats = $record->getBehaviorStats() ?? [];

        // 将 behaviorStats 转换为 behaviorData 格式
        $behaviorData = $this->behaviorProcessor->convertStatsToDataFormat($behaviorStats);

        $this->processStudyValidation($record, $behaviorData);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * 获取用户学时统计
     *
     * @return array<string, mixed>
     */
    public function getUserStudyTimeStats(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->recordRepository->getUserEfficiencyStats($userId, $startDate, $endDate);
    }

    /**
     * 获取课程学时统计
     *
     * @return array<string, mixed>
     */
    public function getCourseStudyTimeStats(string $courseId): array
    {
        return $this->recordRepository->getCourseStudyTimeStats($courseId);
    }
}
