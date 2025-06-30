<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

class LearnBehaviorTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnBehavior();
        
        $this->assertInstanceOf(LearnBehavior::class, $entity);
    }
    
    public function testSessionProperty(): void
    {
        $entity = new LearnBehavior();
        $session = $this->createMock(LearnSession::class);
        
        $entity->setSession($session);
        
        $this->assertSame($session, $entity->getSession());
    }
    
    public function testBehaviorTypeProperty(): void
    {
        $entity = new LearnBehavior();
        $behaviorType = BehaviorType::PLAY;
        
        $entity->setBehaviorType($behaviorType);
        
        $this->assertEquals($behaviorType, $entity->getBehaviorType());
    }
    
    public function testBehaviorDataProperty(): void
    {
        $entity = new LearnBehavior();
        $data = [
            'action' => 'play',
            'timestamp' => '2024-01-01 10:00:00',
            'duration' => 30
        ];
        
        $entity->setBehaviorData($data);
        
        $this->assertEquals($data, $entity->getBehaviorData());
    }
    
    public function testVideoTimestampProperty(): void
    {
        $entity = new LearnBehavior();
        $timestamp = '123.456';
        
        $entity->setVideoTimestamp($timestamp);
        
        $this->assertEquals($timestamp, $entity->getVideoTimestamp());
    }
    
    public function testDeviceFingerprintProperty(): void
    {
        $entity = new LearnBehavior();
        $fingerprint = 'abc123def456';
        
        $entity->setDeviceFingerprint($fingerprint);
        
        $this->assertEquals($fingerprint, $entity->getDeviceFingerprint());
    }
    
    public function testIpAddressProperty(): void
    {
        $entity = new LearnBehavior();
        $ipAddress = '192.168.1.100';
        
        $entity->setIpAddress($ipAddress);
        
        $this->assertEquals($ipAddress, $entity->getIpAddress());
    }
    
    public function testIsSuspiciousProperty(): void
    {
        $entity = new LearnBehavior();
        
        // 测试默认值
        $this->assertFalse($entity->isSuspicious());
        
        // 测试设置新值
        $entity->setIsSuspicious(true);
        $entity->setSuspiciousReason('检测到快速跳转');
        
        $this->assertTrue($entity->isSuspicious());
        $this->assertEquals('检测到快速跳转', $entity->getSuspiciousReason());
    }
    
    public function testSetBehaviorTypeWithSuspiciousBehavior(): void
    {
        $entity = new LearnBehavior();
        
        // 使用实际的可疑行为类型
        $suspiciousBehaviorType = BehaviorType::DEVELOPER_TOOLS;
        
        $entity->setBehaviorType($suspiciousBehaviorType);
        
        $this->assertTrue($entity->isSuspicious());
        $this->assertStringContainsString('系统自动检测', $entity->getSuspiciousReason());
    }
    
    public function testIsFocusRelated(): void
    {
        $entity = new LearnBehavior();
        
        // 测试焦点相关行为
        $entity->setBehaviorType(BehaviorType::WINDOW_FOCUS);
        $this->assertTrue($entity->isFocusRelated());
        
        $entity->setBehaviorType(BehaviorType::WINDOW_BLUR);
        $this->assertTrue($entity->isFocusRelated());
        
        // 测试非焦点相关行为
        $entity->setBehaviorType(BehaviorType::PLAY);
        $this->assertFalse($entity->isFocusRelated());
    }
    
    public function testGetBehaviorCategory(): void
    {
        $entity = new LearnBehavior();
        $behaviorType = BehaviorType::PLAY;
        
        $entity->setBehaviorType($behaviorType);
        
        $this->assertEquals('video_control', $entity->getBehaviorCategory());
    }
    
    public function testIsVideoControl(): void
    {
        $entity = new LearnBehavior();
        
        // 使用视频控制类型的行为
        $videoControlType = BehaviorType::PLAY;
        
        $entity->setBehaviorType($videoControlType);
        
        $this->assertTrue($entity->isVideoControl());
    }
}