<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

class LearnAnomalyTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnAnomaly();
        
        $this->assertInstanceOf(LearnAnomaly::class, $entity);
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDetectedTime());
    }
    
    public function testSessionProperty(): void
    {
        $entity = new LearnAnomaly();
        $session = $this->createMock(LearnSession::class);
        
        $entity->setSession($session);
        
        $this->assertSame($session, $entity->getSession());
    }
    
    public function testAnomalyTypeProperty(): void
    {
        $entity = new LearnAnomaly();
        $anomalyType = AnomalyType::MULTIPLE_DEVICE;
        
        $entity->setAnomalyType($anomalyType);
        
        $this->assertEquals($anomalyType, $entity->getAnomalyType());
    }
    
    public function testAnomalyDescriptionProperty(): void
    {
        $entity = new LearnAnomaly();
        $description = '检测到多设备同时登录';
        
        $entity->setAnomalyDescription($description);
        
        $this->assertEquals($description, $entity->getAnomalyDescription());
    }
    
    public function testSeverityProperty(): void
    {
        $entity = new LearnAnomaly();
        $severity = AnomalySeverity::HIGH;
        
        $entity->setSeverity($severity);
        
        $this->assertEquals($severity, $entity->getSeverity());
    }
    
    public function testStatusProperty(): void
    {
        $entity = new LearnAnomaly();
        
        // 测试默认值
        $this->assertEquals(AnomalyStatus::DETECTED, $entity->getStatus());
        
        // 测试设置新值
        $newStatus = AnomalyStatus::RESOLVED;
        $entity->setStatus($newStatus);
        
        $this->assertEquals($newStatus, $entity->getStatus());
    }
    
    public function testIsAutoDetectedProperty(): void
    {
        $entity = new LearnAnomaly();
        
        // 测试默认值
        $this->assertTrue($entity->isAutoDetected());
        
        // 测试设置新值
        $entity->setIsAutoDetected(false);
        
        $this->assertFalse($entity->isAutoDetected());
    }
    
    public function testImpactScoreProperty(): void
    {
        $entity = new LearnAnomaly();
        
        // 测试设置正常值
        $entity->setImpactScore(7.5);
        $this->assertEquals(7.5, $entity->getImpactScore());
        
        // 测试设置超出范围的值
        $entity->setImpactScore(15.0);
        $this->assertEquals(10.0, $entity->getImpactScore());
        
        $entity->setImpactScore(-5.0);
        $this->assertEquals(0.0, $entity->getImpactScore());
    }
    
    public function testMarkAsResolved(): void
    {
        $entity = new LearnAnomaly();
        $resolution = '已验证为正常学习行为';
        $resolvedBy = 'admin';
        
        $entity->markAsResolved($resolution, $resolvedBy);
        
        $this->assertEquals(AnomalyStatus::RESOLVED, $entity->getStatus());
        $this->assertEquals($resolution, $entity->getResolution());
        $this->assertEquals($resolvedBy, $entity->getResolvedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getResolvedTime());
    }
    
    public function testIsHighPriority(): void
    {
        $entity = new LearnAnomaly();
        
        // 测试高优先级
        $entity->setSeverity(AnomalySeverity::HIGH);
        $this->assertTrue($entity->isHighPriority());
        
        $entity->setSeverity(AnomalySeverity::CRITICAL);
        $this->assertTrue($entity->isHighPriority());
        
        // 测试非高优先级
        $entity->setSeverity(AnomalySeverity::LOW);
        $this->assertFalse($entity->isHighPriority());
    }
}