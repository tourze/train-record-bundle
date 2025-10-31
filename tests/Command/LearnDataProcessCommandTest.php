<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\LearnDataProcessCommand;

/**
 * LearnDataProcessCommand 单元测试
 *
 * @internal
 */
#[CoversClass(LearnDataProcessCommand::class)]
#[RunTestsInSeparateProcesses]
final class LearnDataProcessCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getService(LearnDataProcessCommand::class);

        $this->assertNotNull($command);
        $this->assertEquals('learn:data:process', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(LearnDataProcessCommand::class);

        $this->assertEquals('处理学习数据，计算有效学习时长', $command->getDescription());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionSessionId(): void
    {
        $exitCode = $this->commandTester->execute(['--session-id' => 'test-session']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习数据处理', $this->commandTester->getDisplay());
    }

    public function testOptionUserId(): void
    {
        $exitCode = $this->commandTester->execute(['--user-id' => 'test-user']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习数据处理', $this->commandTester->getDisplay());
    }

    public function testOptionDate(): void
    {
        $exitCode = $this->commandTester->execute(['--date' => '2024-01-01']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习数据处理', $this->commandTester->getDisplay());
    }

    public function testOptionBatchSize(): void
    {
        $exitCode = $this->commandTester->execute(['--batch-size' => '100']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习数据处理', $this->commandTester->getDisplay());
    }

    public function testOptionDryRun(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习数据处理', $this->commandTester->getDisplay());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(LearnDataProcessCommand::class);
        $this->assertInstanceOf(LearnDataProcessCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('learn:data:process');
        $this->commandTester = new CommandTester($command);
    }
}
