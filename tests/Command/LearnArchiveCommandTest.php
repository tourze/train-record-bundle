<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\LearnArchiveCommand;

/**
 * LearnArchiveCommand 单元测试
 *
 * @internal
 */
#[CoversClass(LearnArchiveCommand::class)]
#[RunTestsInSeparateProcesses]
final class LearnArchiveCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getService(LearnArchiveCommand::class);

        $this->assertNotNull($command);
        $this->assertEquals('learn:archive', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(LearnArchiveCommand::class);

        $this->assertEquals('归档完成的学习记录', $command->getDescription());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionUserId(): void
    {
        $exitCode = $this->commandTester->execute(['--user-id' => 'test-user']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionCourseId(): void
    {
        $exitCode = $this->commandTester->execute(['--course-id' => 'test-course']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionArchiveId(): void
    {
        $exitCode = $this->commandTester->execute(['--archive-id' => 'test-archive']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionAction(): void
    {
        $exitCode = $this->commandTester->execute(['--action' => 'create']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionFormat(): void
    {
        $exitCode = $this->commandTester->execute(['--format' => 'json']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionExportPath(): void
    {
        $exitCode = $this->commandTester->execute(['--export-path' => '/tmp/test-export']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionDaysBeforeExpiry(): void
    {
        $exitCode = $this->commandTester->execute(['--days-before-expiry' => '30']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionBatchSize(): void
    {
        $exitCode = $this->commandTester->execute(['--batch-size' => '20']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    public function testOptionDryRun(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习档案管理', $this->commandTester->getDisplay());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(LearnArchiveCommand::class);
        $this->assertInstanceOf(LearnArchiveCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('learn:archive');
        $this->commandTester = new CommandTester($command);
    }
}
