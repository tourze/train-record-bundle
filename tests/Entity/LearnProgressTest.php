<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnProgress;

/**
 * @internal
 */
#[CoversClass(LearnProgress::class)]
#[RunTestsInSeparateProcesses]
final class LearnProgressTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnProgress();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'userId' => ['userId', 'user123'];
        yield 'progress' => ['progress', 75.5];
        yield 'watchedDuration' => ['watchedDuration', 3600.5];
        yield 'effectiveDuration' => ['effectiveDuration', 2400.75];
        yield 'qualityScore' => ['qualityScore', 8.5];
        yield 'lastUpdateDevice' => ['lastUpdateDevice', 'device-123'];
        yield 'lastUpdateTime' => ['lastUpdateTime', new \DateTimeImmutable()];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testUserIdProperty(): void
    {
        $entity = new LearnProgress();
        $userId = '123456';
        $entity->setUserId($userId);
        $this->assertEquals($userId, $entity->getUserId());
    }

    public function testCourseProperty(): void
    {
        $entity = new LearnProgress();
        $course = new Course();
        $entity->setCourse($course);
        $this->assertSame($course, $entity->getCourse());
    }

    public function testLessonProperty(): void
    {
        $entity = new LearnProgress();
        $lesson = new Lesson();
        $entity->setLesson($lesson);
        $this->assertSame($lesson, $entity->getLesson());
    }

    public function testProgressProperty(): void
    {
        $entity = new LearnProgress();
        $progress = 75.5;
        $entity->setProgress($progress);
        $this->assertEquals($progress, $entity->getProgress());
    }

    public function testWatchedDurationProperty(): void
    {
        $entity = new LearnProgress();
        $watchedDuration = 3600.5;
        $entity->setWatchedDuration($watchedDuration);
        $this->assertEquals($watchedDuration, $entity->getWatchedDuration());
    }

    public function testEffectiveDurationProperty(): void
    {
        $entity = new LearnProgress();
        $effectiveDuration = 2400.75;
        $entity->setEffectiveDuration($effectiveDuration);
        $this->assertEquals($effectiveDuration, $entity->getEffectiveDuration());
    }

    public function testIsCompletedProperty(): void
    {
        $entity = new LearnProgress();
        // 测试默认值
        $this->assertFalse($entity->isCompleted());
        // 测试设置为已完成
        $entity->setIsCompleted(true);
        $this->assertTrue($entity->isCompleted());
        // 测试设置为未完成
        $entity->setIsCompleted(false);
        $this->assertFalse($entity->isCompleted());
    }

    public function testQualityScoreProperty(): void
    {
        $entity = new LearnProgress();
        $qualityScore = 8.5;
        $entity->setQualityScore($qualityScore);
        $this->assertEquals($qualityScore, $entity->getQualityScore());
    }

    public function testUpdateProgress(): void
    {
        $entity = new LearnProgress();
        $progress = 50.0;
        $watchedDuration = 1800.0;
        $device = 'test-device';

        $entity->updateProgress($progress, $watchedDuration, $device);

        $this->assertEquals($progress, $entity->getProgress());
        $this->assertEquals($watchedDuration, $entity->getWatchedDuration());
        $this->assertEquals($device, $entity->getLastUpdateDevice());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getLastUpdateTime());
    }

    public function testAddWatchedSegment(): void
    {
        $entity = new LearnProgress();
        $start = 30;
        $end = 60;

        $entity->addWatchedSegment($start, $end);

        $segments = $entity->getWatchedSegments();
        $this->assertNotNull($segments);
        $this->assertCount(1, $segments);
        $this->assertArrayHasKey(0, $segments);
        $this->assertEquals($start, $segments[0]['start']);
        $this->assertEquals($end, $segments[0]['end']);
        $this->assertEquals(30, $segments[0]['duration']);
    }

    public function testGetLearningEfficiency(): void
    {
        $entity = new LearnProgress();
        // 测试无观看时长的情况
        $this->assertEquals(0.0, $entity->getLearningEfficiency());
        // 测试有时长的情况
        $entity->setWatchedDuration(1000.0);
        $entity->setEffectiveDuration(800.0);
        $this->assertEquals(0.8, $entity->getLearningEfficiency());
    }

    public function testNeedsSync(): void
    {
        $entity = new LearnProgress();
        $lastSyncTime = new \DateTimeImmutable('-1 hour');

        // 测试新创建的进度（没有更新时间）
        $this->assertFalse($entity->needsSync($lastSyncTime));

        // 测试有更新的进度
        $entity->setLastUpdateTime(new \DateTimeImmutable());
        $this->assertTrue($entity->needsSync($lastSyncTime));

        // 测试更新时间早于同步时间
        $entity->setLastUpdateTime(new \DateTimeImmutable('-2 hours'));
        $this->assertFalse($entity->needsSync($lastSyncTime));
    }
}
