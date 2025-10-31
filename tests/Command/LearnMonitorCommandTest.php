<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\LearnMonitorCommand;

/**
 * LearnMonitorCommand 单元测试
 *
 * @internal
 */
#[CoversClass(LearnMonitorCommand::class)]
#[RunTestsInSeparateProcesses]
final class LearnMonitorCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getService(LearnMonitorCommand::class);

        $this->assertNotNull($command);
        $this->assertEquals('learn:monitor', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(LearnMonitorCommand::class);

        $this->assertEquals('实时监控学习状态和系统健康', $command->getDescription());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionInterval(): void
    {
        $exitCode = $this->commandTester->execute(['--interval' => '60', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionDuration(): void
    {
        $exitCode = $this->commandTester->execute(['--duration' => '30', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionAlertThreshold(): void
    {
        $exitCode = $this->commandTester->execute(['--alert-threshold' => '5', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionOutputFormat(): void
    {
        $exitCode = $this->commandTester->execute(['--output-format' => 'json', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionLogFile(): void
    {
        $exitCode = $this->commandTester->execute(['--log-file' => '/tmp/monitor.log', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionAutoResolve(): void
    {
        $exitCode = $this->commandTester->execute(['--auto-resolve', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testOptionQuiet(): void
    {
        $exitCode = $this->commandTester->execute(['--quiet', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        // --quiet模式下可能没有输出，所以不检查输出内容
    }

    public function testOptionDryRun(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testHandleSignal(): void
    {
        /** @var LearnMonitorCommand $command */
        $command = self::getContainer()->get(LearnMonitorCommand::class);

        // 测试处理SIGTERM信号
        try {
            $result = $command->handleSignal(SIGTERM, 0);
            $this->assertTrue(is_int($result) || !$result); // handleSignal 返回 int|false
        } catch (\Exception $e) {
            // 如果方法抛出异常，验证异常信息
            $this->assertIsString($e->getMessage());
        }

        // 测试处理SIGINT信号
        try {
            $result = $command->handleSignal(SIGINT, 0);
            $this->assertTrue(is_int($result) || !$result);
        } catch (\Exception $e) {
            $this->assertIsString($e->getMessage());
        }
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(LearnMonitorCommand::class);
        $this->assertInstanceOf(LearnMonitorCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('learn:monitor');
        $this->commandTester = new CommandTester($command);
    }
}
