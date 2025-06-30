<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Entity\FaceDetect;

class FaceDetectTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new FaceDetect();
        
        $this->assertInstanceOf(FaceDetect::class, $entity);
    }
    
    public function testConfidenceProperty(): void
    {
        $entity = new FaceDetect();
        $confidence = '0.95';
        
        $entity->setConfidence($confidence);
        
        $this->assertEquals($confidence, $entity->getConfidence());
    }
    
    public function testSimilarityProperty(): void
    {
        $entity = new FaceDetect();
        $similarity = '0.88';
        
        $entity->setSimilarity($similarity);
        
        $this->assertEquals($similarity, $entity->getSimilarity());
    }
    
    public function testIsVerifiedProperty(): void
    {
        $entity = new FaceDetect();
        
        $entity->setIsVerified(true);
        
        $this->assertTrue($entity->isVerified());
    }
}