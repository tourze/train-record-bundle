<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;

class LearnLogTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnLog();
        
        $this->assertInstanceOf(LearnLog::class, $entity);
    }
    
    public function testIdProperty(): void
    {
        $entity = new LearnLog();
        
        // 测试默认值
        $this->assertEquals(0, $entity->getId());
    }
    
    public function testLearnSessionProperty(): void
    {
        $entity = new LearnLog();
        $session = $this->createMock(LearnSession::class);
        
        $entity->setLearnSession($session);
        
        $this->assertSame($session, $entity->getLearnSession());
    }
    
    public function testStudentProperty(): void
    {
        $entity = new LearnLog();
        $student = new \stdClass(); // 模拟BizUser对象
        
        $entity->setStudent($student);
        
        $this->assertSame($student, $entity->getStudent());
    }
    
    public function testRegistrationProperty(): void
    {
        $entity = new LearnLog();
        $registration = $this->createMock(Registration::class);
        
        $entity->setRegistration($registration);
        
        $this->assertSame($registration, $entity->getRegistration());
    }
    
    public function testLessonProperty(): void
    {
        $entity = new LearnLog();
        $lesson = $this->createMock(Lesson::class);
        
        $entity->setLesson($lesson);
        
        $this->assertSame($lesson, $entity->getLesson());
    }
    
    public function testActionProperty(): void
    {
        $entity = new LearnLog();
        $action = LearnAction::START;
        
        $entity->setAction($action);
        
        $this->assertEquals($action, $entity->getAction());
    }
    
    public function testCreatedFromUaProperty(): void
    {
        $entity = new LearnLog();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        $entity->setCreatedFromUa($userAgent);
        
        $this->assertEquals($userAgent, $entity->getCreatedFromUa());
    }
    
    public function testCreateTimeProperty(): void
    {
        $entity = new LearnLog();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        
        $entity->setCreateTime($createTime);
        
        $this->assertSame($createTime, $entity->getCreateTime());
    }
    
    public function testCreatedFromIpProperty(): void
    {
        $entity = new LearnLog();
        $ipAddress = '192.168.1.100';
        
        $entity->setCreatedFromIp($ipAddress);
        
        $this->assertEquals($ipAddress, $entity->getCreatedFromIp());
    }
    
    public function testToString(): void
    {
        $entity = new LearnLog();
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, 123);
        
        $this->assertEquals('123', (string) $entity);
    }
}