<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\LearnStatisticsCommand;

/**
 * LearnStatisticsCommand 单元测试
 *
 * @internal
 */
#[CoversClass(LearnStatisticsCommand::class)]
#[RunTestsInSeparateProcesses]
final class LearnStatisticsCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);

        $this->assertNotNull($command);
        $this->assertEquals('learn:statistics', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);

        $this->assertEquals('生成学习统计数据', $command->getDescription());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testRealCommandExecute(): void
    {
        $exitCode = $this->commandTester->execute(['--type' => 'user', '--period' => 'daily', '--days' => '1']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('学习统计生成', $this->commandTester->getDisplay());
        $this->assertStringContainsString('统计生成完成', $this->commandTester->getDisplay());
    }

    public function testOptionType(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('type'));
        $typeOption = $definition->getOption('type');
        $this->assertEquals('t', $typeOption->getShortcut());
        $this->assertEquals('统计类型 (user, course, behavior, anomaly, device, progress, duration, efficiency, completion, engagement, quality, trend)', $typeOption->getDescription());
        $this->assertEquals('user', $typeOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--type' => 'course', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionPeriod(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('period'));
        $periodOption = $definition->getOption('period');
        $this->assertEquals('p', $periodOption->getShortcut());
        $this->assertEquals('统计周期 (realtime, hourly, daily, weekly, monthly, quarterly, yearly)', $periodOption->getDescription());
        $this->assertEquals('daily', $periodOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--period' => 'weekly', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionDate(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('date'));
        $dateOption = $definition->getOption('date');
        $this->assertEquals('d', $dateOption->getShortcut());
        $this->assertEquals('统计日期 (Y-m-d)', $dateOption->getDescription());
        $this->assertEquals(date('Y-m-d'), $dateOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--date' => '2024-01-01', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionUserId(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('user-id'));
        $userIdOption = $definition->getOption('user-id');
        $this->assertEquals('u', $userIdOption->getShortcut());
        $this->assertEquals('指定用户ID（用于用户统计）', $userIdOption->getDescription());
        $this->assertNull($userIdOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--user-id' => '123', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionCourseId(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('course-id'));
        $courseIdOption = $definition->getOption('course-id');
        $this->assertEquals('c', $courseIdOption->getShortcut());
        $this->assertEquals('指定课程ID（用于课程统计）', $courseIdOption->getDescription());
        $this->assertNull($courseIdOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--course-id' => '456', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionDays(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('days'));
        $daysOption = $definition->getOption('days');
        $this->assertNull($daysOption->getShortcut());
        $this->assertEquals('统计天数（向前追溯）', $daysOption->getDescription());
        $this->assertEquals(7, $daysOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--days' => '14', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionFormat(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('format'));
        $formatOption = $definition->getOption('format');
        $this->assertEquals('f', $formatOption->getShortcut());
        $this->assertEquals('输出格式 (table, json, csv)', $formatOption->getDescription());
        $this->assertEquals('table', $formatOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--format' => 'json', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionSave(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('save'));
        $saveOption = $definition->getOption('save');
        $this->assertEquals('s', $saveOption->getShortcut());
        $this->assertEquals('保存统计结果到数据库', $saveOption->getDescription());
        $this->assertFalse($saveOption->isValueRequired());
        $this->assertFalse($saveOption->isValueOptional());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--save', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionExport(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('export'));
        $exportOption = $definition->getOption('export');
        $this->assertEquals('e', $exportOption->getShortcut());
        $this->assertEquals('导出文件路径', $exportOption->getDescription());
        $this->assertNull($exportOption->getDefault());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--export' => '/tmp/stats.json', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionBatchGenerate(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('batch-generate'));
        $batchOption = $definition->getOption('batch-generate');
        $this->assertEquals('b', $batchOption->getShortcut());
        $this->assertEquals('批量生成所有类型的统计', $batchOption->getDescription());
        $this->assertFalse($batchOption->isValueRequired());
        $this->assertFalse($batchOption->isValueOptional());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--batch-generate', '--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionDryRun(): void
    {
        $command = self::getService(LearnStatisticsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
        $dryRunOption = $definition->getOption('dry-run');
        $this->assertNull($dryRunOption->getShortcut());
        $this->assertEquals('仅模拟执行，不进行实际统计计算', $dryRunOption->getDescription());
        $this->assertFalse($dryRunOption->isValueRequired());
        $this->assertFalse($dryRunOption->isValueOptional());

        // 功能性测试
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(LearnStatisticsCommand::class);
        $this->assertInstanceOf(LearnStatisticsCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('learn:statistics');
        $this->commandTester = new CommandTester($command);
    }
}
