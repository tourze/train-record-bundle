<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnProgress;

class LearnProgressTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnProgress();
        
        $this->assertInstanceOf(LearnProgress::class, $entity);
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
        $course = $this->createMock(Course::class);
        
        $entity->setCourse($course);
        
        $this->assertSame($course, $entity->getCourse());
    }
    
    public function testLessonProperty(): void
    {
        $entity = new LearnProgress();
        $lesson = $this->createMock(Lesson::class);
        
        $entity->setLesson($lesson);
        
        $this->assertSame($lesson, $entity->getLesson());
    }
    
    public function testProgressProperty(): void
    {
        $entity = new LearnProgress();
        
        // 测试默认值
        $this->assertEquals(0.0, $entity->getProgress());
        
        // 测试设置正常值
        $entity->setProgress(75.5);
        $this->assertEquals(75.5, $entity->getProgress());
        
        // 测试边界值
        $entity->setProgress(150.0);
        $this->assertEquals(100.0, $entity->getProgress());
        
        $entity->setProgress(-10.0);
        $this->assertEquals(0.0, $entity->getProgress());
    }
    
    public function testWatchedDurationProperty(): void
    {
        $entity = new LearnProgress();
        
        // 测试默认值
        $this->assertEquals(0.0, $entity->getWatchedDuration());
        
        // 测试设置值
        $entity->setWatchedDuration(3600.5);
        $this->assertEquals(3600.5, $entity->getWatchedDuration());
        
        // 测试负数处理
        $entity->setWatchedDuration(-100.0);
        $this->assertEquals(0.0, $entity->getWatchedDuration());
    }
    
    public function testEffectiveDurationProperty(): void
    {
        $entity = new LearnProgress();
        
        // 测试默认值
        $this->assertEquals(0.0, $entity->getEffectiveDuration());
        
        // 测试设置值
        $entity->setEffectiveDuration(2400.75);
        $this->assertEquals(2400.75, $entity->getEffectiveDuration());
    }
    
    public function testIsCompletedProperty(): void
    {
        $entity = new LearnProgress();
        
        // 测试默认值
        $this->assertFalse($entity->isCompleted());
        
        // 测试设置值
        $entity->setIsCompleted(true);
        $this->assertTrue($entity->isCompleted());
    }
    
    public function testQualityScoreProperty(): void
    {
        $entity = new LearnProgress();
        
        // 测试设置正常值
        $entity->setQualityScore(8.5);
        $this->assertEquals(8.5, $entity->getQualityScore());
        
        // 测试边界值
        $entity->setQualityScore(15.0);
        $this->assertEquals(10.0, $entity->getQualityScore());
        
        $entity->setQualityScore(-5.0);
        $this->assertEquals(0.0, $entity->getQualityScore());
    }
    
    public function testUpdateProgress(): void
    {
        $entity = new LearnProgress();
        $deviceFingerprint = 'device-123';
        
        $entity->updateProgress(50.0, 1800.0, $deviceFingerprint);
        
        $this->assertEquals(50.0, $entity->getProgress());
        $this->assertEquals(1800.0, $entity->getWatchedDuration());
        $this->assertEquals($deviceFingerprint, $entity->getLastUpdateDevice());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getLastUpdateTime());
        $this->assertIsArray($entity->getProgressHistory());
        $this->assertCount(1, $entity->getProgressHistory());
        
        // 测试自动完成
        $entity->updateProgress(100.0, 3600.0);
        $this->assertTrue($entity->isCompleted());
    }
    
    public function testAddWatchedSegment(): void
    {
        $entity = new LearnProgress();
        
        $entity->addWatchedSegment(0.0, 30.0);
        $entity->addWatchedSegment(35.0, 65.0);
        
        $segments = $entity->getWatchedSegments();
        $this->assertIsArray($segments);
        $this->assertCount(2, $segments);
        $this->assertEquals(30.0, $segments[0]['duration']);
        $this->assertEquals(30.0, $segments[1]['duration']);
    }
    
    public function testGetLearningEfficiency(): void
    {
        $entity = new LearnProgress();
        
        // 测试无观看时长的情况
        $this->assertEquals(0.0, $entity->getLearningEfficiency());
        
        // 测试有观看时长的情况
        $entity->setWatchedDuration(100.0);
        $entity->setEffectiveDuration(80.0);
        
        $this->assertEquals(0.8, $entity->getLearningEfficiency());
    }
    
    public function testNeedsSync(): void
    {
        $entity = new LearnProgress();
        $lastSyncTime = new \DateTimeImmutable('-1 hour');
        
        // 初始状态不需要同步
        $this->assertFalse($entity->needsSync($lastSyncTime));
        
        // 更新后需要同步
        $entity->setLastUpdateTime(new \DateTimeImmutable());
        $this->assertTrue($entity->needsSync($lastSyncTime));
    }
}