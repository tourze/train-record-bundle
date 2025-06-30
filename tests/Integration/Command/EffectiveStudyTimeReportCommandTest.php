<?php

namespace Tourze\TrainRecordBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Command\EffectiveStudyTimeReportCommand;

class EffectiveStudyTimeReportCommandTest extends TestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $this->markTestSkipped('需要完整的依赖注入容器配置');
    }
}