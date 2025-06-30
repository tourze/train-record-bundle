<?php

namespace Tourze\TrainRecordBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\TrainRecordBundle;

class TrainRecordBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new TrainRecordBundle();
        
        $this->assertInstanceOf(TrainRecordBundle::class, $bundle);
    }
}