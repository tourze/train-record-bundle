<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

class LearnArchiveTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnArchive();
        
        $this->assertInstanceOf(LearnArchive::class, $entity);
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getArchiveDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getExpiryDate());
    }
    
    public function testUserIdProperty(): void
    {
        $entity = new LearnArchive();
        $userId = '123456';
        
        $entity->setUserId($userId);
        
        $this->assertEquals($userId, $entity->getUserId());
    }
    
    public function testCourseProperty(): void
    {
        $entity = new LearnArchive();
        $course = $this->createMock(Course::class);
        
        $entity->setCourse($course);
        
        $this->assertSame($course, $entity->getCourse());
    }
    
    public function testTotalEffectiveTimeProperty(): void
    {
        $entity = new LearnArchive();
        $time = 3600.5;
        
        $entity->setTotalEffectiveTime($time);
        
        $this->assertEquals($time, $entity->getTotalEffectiveTime());
    }
    
    public function testTotalSessionsProperty(): void
    {
        $entity = new LearnArchive();
        $sessions = 10;
        
        $entity->setTotalSessions($sessions);
        
        $this->assertEquals($sessions, $entity->getTotalSessions());
        
        // 测试负数处理
        $entity->setTotalSessions(-5);
        $this->assertEquals(0, $entity->getTotalSessions());
    }
    
    public function testArchiveStatusProperty(): void
    {
        $entity = new LearnArchive();
        
        // 测试默认值
        $this->assertEquals(ArchiveStatus::ACTIVE, $entity->getArchiveStatus());
        
        // 测试设置新值
        $newStatus = ArchiveStatus::ARCHIVED;
        $entity->setArchiveStatus($newStatus);
        
        $this->assertEquals($newStatus, $entity->getArchiveStatus());
    }
    
    public function testArchiveFormatProperty(): void
    {
        $entity = new LearnArchive();
        
        // 测试默认值
        $this->assertEquals(ArchiveFormat::JSON, $entity->getArchiveFormat());
        
        // 测试设置新值
        $newFormat = ArchiveFormat::JSON;
        $entity->setArchiveFormat($newFormat);
        
        $this->assertEquals($newFormat, $entity->getArchiveFormat());
    }
    
    public function testFileSizeProperty(): void
    {
        $entity = new LearnArchive();
        
        // 测试设置正常值
        $entity->setFileSize(1024);
        $this->assertEquals(1024, $entity->getFileSize());
        
        // 测试设置负数
        $entity->setFileSize(-100);
        $this->assertEquals(0, $entity->getFileSize());
    }
    
    public function testCompressionRatioProperty(): void
    {
        $entity = new LearnArchive();
        
        // 测试设置正常值
        $entity->setCompressionRatio(75.5);
        $this->assertEquals(75.5, $entity->getCompressionRatio());
        
        // 测试设置超出范围的值
        $entity->setCompressionRatio(150.0);
        $this->assertEquals(100.0, $entity->getCompressionRatio());
        
        $entity->setCompressionRatio(-10.0);
        $this->assertEquals(0.0, $entity->getCompressionRatio());
    }
    
    public function testMarkAsArchived(): void
    {
        $entity = new LearnArchive();
        $path = '/path/to/archive.zip';
        $hash = 'abc123hash';
        
        $entity->markAsArchived($path, $hash);
        
        $this->assertEquals(ArchiveStatus::ARCHIVED, $entity->getArchiveStatus());
        $this->assertEquals($path, $entity->getArchivePath());
        $this->assertEquals($hash, $entity->getArchiveHash());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getArchiveDate());
    }
    
    public function testIsExpired(): void
    {
        $entity = new LearnArchive();
        
        // 测试未过期（默认3年后过期）
        $this->assertFalse($entity->isExpired());
        
        // 测试已过期
        $entity->setExpiryDate(new \DateTimeImmutable('-1 day'));
        $this->assertTrue($entity->isExpired());
    }
    
    public function testGetFormattedFileSize(): void
    {
        $entity = new LearnArchive();
        
        // 测试字节
        $entity->setFileSize(500);
        $this->assertEquals('500 B', $entity->getFormattedFileSize());
        
        // 测试KB
        $entity->setFileSize(2048);
        $this->assertEquals('2 KB', $entity->getFormattedFileSize());
        
        // 测试MB
        $entity->setFileSize(1048576);
        $this->assertEquals('1 MB', $entity->getFormattedFileSize());
    }
}