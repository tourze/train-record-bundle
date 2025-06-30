<?php

namespace Tourze\TrainRecordBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TrainRecordBundle\Command\EffectiveStudyTimeRecalculateCommand;

class EffectiveStudyTimeRecalculateCommandTest extends TestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $this->markTestSkipped('需要完整的依赖注入容器配置');
    }
}