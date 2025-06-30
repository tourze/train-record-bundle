<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;

class EffectiveStudyRecordTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new EffectiveStudyRecord();
        
        $this->assertInstanceOf(EffectiveStudyRecord::class, $entity);
    }
    
    public function testUserIdProperty(): void
    {
        $entity = new EffectiveStudyRecord();
        $userId = '12345';
        
        $entity->setUserId($userId);
        
        $this->assertEquals($userId, $entity->getUserId());
    }
    
    public function testCourseProperty(): void
    {
        $entity = new EffectiveStudyRecord();
        $course = $this->createMock(\Tourze\TrainCourseBundle\Entity\Course::class);
        
        $entity->setCourse($course);
        
        $this->assertSame($course, $entity->getCourse());
    }
}