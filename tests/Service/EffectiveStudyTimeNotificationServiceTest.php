<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeNotificationService;

/**
 * @internal
 */
#[CoversClass(EffectiveStudyTimeNotificationService::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyTimeNotificationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);
        $this->assertInstanceOf(EffectiveStudyTimeNotificationService::class, $service);
    }

    public function testSendRealtimeStudyStatus(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        $userId = 'user123';
        $statusData = [
            'effective_time' => 1800.0,
            'total_time' => 2400.0,
        ];

        $result = $service->sendRealtimeStudyStatus($userId, $statusData);

        $this->assertTrue($result);
    }

    public function testSendInvalidTimeNotification(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        $userId = 'user123';
        $reason = InvalidTimeReason::INTERACTION_TIMEOUT;
        $details = ['timeout_duration' => 600];

        $result = $service->sendInvalidTimeNotification($userId, $reason, $details);

        $this->assertTrue($result);
    }

    public function testSendDailyLimitNotification(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        $userId = 'user123';
        $currentTime = 32400.0; // 9 hours
        $dailyLimit = 28800.0;  // 8 hours
        $exceededTime = 3600.0; // 1 hour

        $result = $service->sendDailyLimitNotification($userId, $currentTime, $dailyLimit, $exceededTime);

        $this->assertTrue($result);
    }

    public function testSendQualityFeedback(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        $userId = 'user123';
        $qualityScore = 8.5;
        $scoreDetails = [
            'focus_score' => 0.9,
            'interaction_score' => 0.8,
            'continuity_score' => 0.85,
        ];

        $result = $service->sendQualityFeedback($userId, $qualityScore, $scoreDetails);

        $this->assertTrue($result);
    }

    public function testSendStudyTimeResultNotification(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        // 使用具体类 EffectiveStudyRecord 而不是接口，因为：
        // 1) EffectiveStudyRecord 是 Doctrine 实体类，没有对应的接口
        // 2) 在测试中需要模拟实体的 getter 方法，这是合理且必要的
        // 3) 没有更好的替代方案，因为服务层直接依赖实体类
        $record = $this->createMock(EffectiveStudyRecord::class);
        $record->method('getUserId')->willReturn('user123');
        $record->method('getId')->willReturn('1');

        // 使用真实的枚举值
        $status = StudyTimeStatus::VALID;
        $record->method('getStatus')->willReturn($status);
        $record->method('getEffectiveDuration')->willReturn(3600.0);
        $record->method('getQualityScore')->willReturn(8.0);
        $record->method('getInvalidReason')->willReturn(null);

        // 使用具体类 Course 而不是接口，因为：
        // 1) Course 是 Doctrine 实体类，没有对应的接口
        // 2) 在测试中需要模拟实体的 getter 方法，这是合理且必要的
        // 3) 没有更好的替代方案，因为服务层直接依赖实体类
        $course = $this->createMock(Course::class);
        $course->method('getTitle')->willReturn('Test Course');
        $record->method('getCourse')->willReturn($course);

        // 使用具体类 Lesson 而不是接口，因为：
        // 1) Lesson 是 Doctrine 实体类，没有对应的接口
        // 2) 在测试中需要模拟实体的 getter 方法，这是合理且必要的
        // 3) 没有更好的替代方案，因为服务层直接依赖实体类
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTitle')->willReturn('Test Lesson');
        $record->method('getLesson')->willReturn($lesson);

        $record->method('getStudyDate')->willReturn(new \DateTimeImmutable('2024-01-01'));

        // 验证方法调用不抛出致命错误
        try {
            $service->sendStudyTimeResultNotification($record);
            $this->assertTrue(true, '方法执行成功');
        } catch (\Throwable $e) {
            self::fail('方法执行失败: ' . $e->getMessage());
        }
    }

    public function testSendPendingNotifications(): void
    {
        $service = self::getService(EffectiveStudyTimeNotificationService::class);

        $sentCount = $service->sendPendingNotifications();

        $this->assertIsInt($sentCount);
        $this->assertGreaterThanOrEqual(0, $sentCount);
    }
}
