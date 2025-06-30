<?php

namespace Tourze\TrainRecordBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;

class LearnBehaviorServiceTest extends TestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        $this->markTestSkipped('需要完整的依赖注入容器配置');
    }
}