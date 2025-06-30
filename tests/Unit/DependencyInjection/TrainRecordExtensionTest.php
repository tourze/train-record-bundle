<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TrainRecordBundle\DependencyInjection\TrainRecordExtension;

class TrainRecordExtensionTest extends TestCase
{
    public function testExtensionCanBeLoaded(): void
    {
        $container = new ContainerBuilder();
        $extension = new TrainRecordExtension();
        
        $extension->load([], $container);
        
        $this->assertTrue(true); // Extension loaded without errors
    }
    
    public function testExtensionCanBeInstantiated(): void
    {
        $extension = new TrainRecordExtension();
        
        $this->assertInstanceOf(TrainRecordExtension::class, $extension);
    }
}