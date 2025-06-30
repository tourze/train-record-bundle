<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;

class LearnSessionTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnSession();
        
        $this->assertInstanceOf(LearnSession::class, $entity);
    }
    
    public function testStudentProperty(): void
    {
        $entity = new LearnSession();
        $student = $this->createMock(UserInterface::class);
        
        $entity->setStudent($student);
        
        $this->assertSame($student, $entity->getStudent());
    }
    
    public function testRegistrationProperty(): void
    {
        $entity = new LearnSession();
        $registration = $this->createMock(Registration::class);
        
        $entity->setRegistration($registration);
        
        $this->assertSame($registration, $entity->getRegistration());
    }
    
    public function testCourseProperty(): void
    {
        $entity = new LearnSession();
        $course = $this->createMock(Course::class);
        
        $entity->setCourse($course);
        
        $this->assertSame($course, $entity->getCourse());
    }
    
    public function testLessonProperty(): void
    {
        $entity = new LearnSession();
        $lesson = $this->createMock(Lesson::class);
        
        $entity->setLesson($lesson);
        
        $this->assertSame($lesson, $entity->getLesson());
    }
    
    public function testFirstLearnTimeProperty(): void
    {
        $entity = new LearnSession();
        $registration = $this->createMock(Registration::class);
        $registration->method('getFirstLearnTime')->willReturn(null);
        $registration->expects($this->once())->method('setFirstLearnTime');
        
        $entity->setRegistration($registration);
        
        $time = new \DateTimeImmutable('2024-01-01 10:00:00');
        $entity->setFirstLearnTime($time);
        
        $this->assertSame($time, $entity->getFirstLearnTime());
    }
    
    public function testLastLearnTimeProperty(): void
    {
        $entity = new LearnSession();
        $registration = $this->createMock(Registration::class);
        $registration->expects($this->once())->method('setLastLearnTime');
        
        $entity->setRegistration($registration);
        
        $time = new \DateTimeImmutable('2024-01-01 11:00:00');
        $entity->setLastLearnTime($time);
        
        $this->assertSame($time, $entity->getLastLearnTime());
    }
    
    public function testFinishedProperty(): void
    {
        $entity = new LearnSession();
        
        // 测试默认值
        $this->assertFalse($entity->isFinished());
        
        // 测试设置值
        $entity->setFinished(true);
        $this->assertTrue($entity->isFinished());
    }
    
    public function testActiveProperty(): void
    {
        $entity = new LearnSession();
        
        // 测试默认值
        $this->assertFalse($entity->isActive());
        
        // 测试设置值
        $entity->setActive(true);
        $this->assertTrue($entity->isActive());
    }
    
    public function testCurrentDurationProperty(): void
    {
        $entity = new LearnSession();
        
        // 测试默认值
        $this->assertEquals('0.00', $entity->getCurrentDuration());
        
        // 测试设置值
        $entity->setCurrentDuration('3600.50');
        $this->assertEquals('3600.50', $entity->getCurrentDuration());
    }
    
    public function testTotalDurationProperty(): void
    {
        $entity = new LearnSession();
        
        // 测试默认值
        $this->assertEquals('0.00', $entity->getTotalDuration());
        
        // 测试设置值
        $entity->setTotalDuration('7200.75');
        $this->assertEquals('7200.75', $entity->getTotalDuration());
    }
    
    public function testDeviceProperty(): void
    {
        $entity = new LearnSession();
        $device = $this->createMock(LearnDevice::class);
        
        $entity->setDevice($device);
        
        $this->assertSame($device, $entity->getDevice());
    }
    
    public function testCreatedFromIpProperty(): void
    {
        $entity = new LearnSession();
        $ip = '192.168.1.100';
        
        $entity->setCreatedFromIp($ip);
        
        $this->assertEquals($ip, $entity->getCreatedFromIp());
    }
    
    public function testCreatedFromUaProperty(): void
    {
        $entity = new LearnSession();
        $userAgent = 'Mozilla/5.0 ...';
        
        $entity->setCreatedFromUa($userAgent);
        
        $this->assertEquals($userAgent, $entity->getCreatedFromUa());
    }
    
    public function testAddAndRemoveLearnLog(): void
    {
        $entity = new LearnSession();
        $learnLog = $this->createMock(LearnLog::class);
        
        // 期望调用 setLearnSession
        $learnLog->expects($this->any())->method('setLearnSession');
        $learnLog->expects($this->any())->method('getLearnSession')->willReturn($entity);
        
        $entity->addLearnLog($learnLog);
        $this->assertCount(1, $entity->getLearnLogs());
        $this->assertTrue($entity->getLearnLogs()->contains($learnLog));
        
        $entity->removeLearnLog($learnLog);
        $this->assertCount(0, $entity->getLearnLogs());
    }
    
    public function testAddAndRemoveLearnBehavior(): void
    {
        $entity = new LearnSession();
        $behavior = $this->createMock(LearnBehavior::class);
        
        // 期望调用 setSession
        $behavior->expects($this->once())->method('setSession')->with($entity);
        
        $entity->addLearnBehavior($behavior);
        $this->assertCount(1, $entity->getLearnBehaviors());
        $this->assertTrue($entity->getLearnBehaviors()->contains($behavior));
        
        // 移除
        $entity->removeLearnBehavior($behavior);
        $this->assertCount(0, $entity->getLearnBehaviors());
    }
}