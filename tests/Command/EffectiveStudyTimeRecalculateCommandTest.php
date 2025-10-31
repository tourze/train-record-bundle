<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\EffectiveStudyTimeRecalculateCommand;

/**
 * EffectiveStudyTimeRecalculateCommand 集成测试
 *
 * @internal
 */
#[CoversClass(EffectiveStudyTimeRecalculateCommand::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyTimeRecalculateCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testExecuteCommand(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
    }

    public function testRealCommandExecute(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
        $this->assertStringContainsString('试运行模式', $this->commandTester->getDisplay());
    }

    public function testOptionRecordId(): void
    {
        $exitCode = $this->commandTester->execute(['--record-id' => 'test-record-id']);
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
        $this->assertStringContainsString('不存在', $this->commandTester->getDisplay());
    }

    public function testOptionUserId(): void
    {
        $exitCode = $this->commandTester->execute(['--user-id' => 'test-user-id']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
    }

    public function testOptionDate(): void
    {
        $exitCode = $this->commandTester->execute(['--date' => '2024-01-01']);
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
        $this->assertStringContainsString('必须提供用户ID', $this->commandTester->getDisplay());
    }

    public function testOptionCourseId(): void
    {
        $exitCode = $this->commandTester->execute(['--course-id' => 'test-course-id']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
    }

    public function testOptionBatchSize(): void
    {
        $exitCode = $this->commandTester->execute(['--batch-size' => '100']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
    }

    public function testOptionOnlyInvalid(): void
    {
        $exitCode = $this->commandTester->execute(['--only-invalid']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时重新计算', $this->commandTester->getDisplay());
    }

    public function testOptionDryRun(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        // dry-run 模式下可能显示"试运行模式"，也可能显示没有找到记录
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(
            str_contains($output, '试运行模式') || str_contains($output, '没有找到需要重新计算的记录'),
            '输出应该包含"试运行模式"或"没有找到需要重新计算的记录"'
        );
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(EffectiveStudyTimeRecalculateCommand::class);
        $this->assertInstanceOf(EffectiveStudyTimeRecalculateCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('train-record:effective-study-time:recalculate');
        $this->commandTester = new CommandTester($command);
    }
}
