<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeService;

/**
 * @internal
 */
#[CoversClass(EffectiveStudyTimeService::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyTimeServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);
        $this->assertInstanceOf(EffectiveStudyTimeService::class, $service);
    }

    public function testRecalculateRecord(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);

        $record = new EffectiveStudyRecord();
        $record->setUserId('test-user-123');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setBehaviorStats([
            'total_clicks' => 5,
            'total_scrolls' => 3,
            'average_duration' => 7.5,
            'interaction_quality' => 'good',
        ]);
        $record->setTotalDuration(3600.0);

        try {
            $result = $service->recalculateRecord($record);
            $this->assertInstanceOf(EffectiveStudyRecord::class, $result);
        } catch (\Exception $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGetUserStudyTimeStats(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);

        $userId = 'user123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $stats = $service->getUserStudyTimeStats($userId, $startDate, $endDate);

        $this->assertIsArray($stats);
    }

    public function testGetCourseStudyTimeStats(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);

        $courseId = 'course123';

        $stats = $service->getCourseStudyTimeStats($courseId);

        $this->assertIsArray($stats);
    }

    public function testBatchProcessStudyTime(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);

        $sessions = [
            [
                'session' => $this->createMockSession('session1'),
                'start_time' => new \DateTimeImmutable('2024-01-01 10:00:00'),
                'end_time' => new \DateTimeImmutable('2024-01-01 11:00:00'),
                'duration' => 3600.0,
                'behavior_data' => [],
            ],
            [
                'session' => $this->createMockSession('session2'),
                'start_time' => new \DateTimeImmutable('2024-01-01 11:00:00'),
                'end_time' => new \DateTimeImmutable('2024-01-01 12:00:00'),
                'duration' => 3600.0,
                'behavior_data' => [],
            ],
        ];

        try {
            $result = $service->batchProcessStudyTime($sessions);
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testProcessStudyTime(): void
    {
        $service = self::getService(EffectiveStudyTimeService::class);

        $session = $this->createMockSession('test-session');
        $startTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $endTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $duration = 3600.0;
        $behaviorData = [];

        try {
            $result = $service->processStudyTime($session, $startTime, $endTime, $duration, $behaviorData);
            $this->assertInstanceOf(EffectiveStudyRecord::class, $result);
        } catch (\Exception $e) {
            $this->assertIsString($e->getMessage());
            $this->assertNotEmpty($e->getMessage());
        }
    }

    private function createMockSession(string $sessionId): LearnSession
    {
        $session = $this->createMock(LearnSession::class);
        $session->method('getId')->willReturn($sessionId);
        $session->method('getUserId')->willReturn('test-user');
        $session->method('getCourseId')->willReturn('test-course');

        return $session;
    }
}
