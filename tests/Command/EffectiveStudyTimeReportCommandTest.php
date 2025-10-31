<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\EffectiveStudyTimeReportCommand;

/**
 * EffectiveStudyTimeReportCommand 集成测试
 *
 * @internal
 */
#[CoversClass(EffectiveStudyTimeReportCommand::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyTimeReportCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testExecuteCommand(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testRealCommandExecute(): void
    {
        $exitCode = $this->commandTester->execute(['--start-date' => '2024-01-01', '--end-date' => '2024-01-02']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
        $this->assertStringContainsString('报告生成完成', $this->commandTester->getDisplay());
    }

    public function testOptionUserId(): void
    {
        $exitCode = $this->commandTester->execute(['--user-id' => 'test-user-id']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionCourseId(): void
    {
        $exitCode = $this->commandTester->execute(['--course-id' => 'test-course-id']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionStartDate(): void
    {
        $exitCode = $this->commandTester->execute(['--start-date' => '2024-01-01']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionEndDate(): void
    {
        $exitCode = $this->commandTester->execute(['--end-date' => '2024-01-02']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionFormat(): void
    {
        $exitCode = $this->commandTester->execute(['--format' => 'json']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionOutputFile(): void
    {
        $exitCode = $this->commandTester->execute(['--output-file' => '/tmp/test-report.json']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    public function testOptionIncludeDetails(): void
    {
        $exitCode = $this->commandTester->execute(['--include-details']);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('有效学时统计报告', $this->commandTester->getDisplay());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(EffectiveStudyTimeReportCommand::class);
        $this->assertInstanceOf(EffectiveStudyTimeReportCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('train-record:effective-study-time:report');
        $this->commandTester = new CommandTester($command);
    }
}
