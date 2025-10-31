<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\LearnSessionCleanupCommand;

/**
 * LearnSessionCleanupCommand 单元测试
 *
 * @internal
 */
#[CoversClass(LearnSessionCleanupCommand::class)]
#[RunTestsInSeparateProcesses]
final class LearnSessionCleanupCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getService(LearnSessionCleanupCommand::class);

        $this->assertNotNull($command);
        $this->assertEquals('train:learn-session:cleanup', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(LearnSessionCleanupCommand::class);

        $this->assertEquals('清理无效的学习会话（3分钟内未更新的活跃会话）', $command->getDescription());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testRealCommandExecute(): void
    {
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('清理无效的学习会话', $this->commandTester->getDisplay());
        $this->assertStringContainsString('模拟运行模式', $this->commandTester->getDisplay());
    }

    public function testOptionDryRun(): void
    {
        $command = self::getService(LearnSessionCleanupCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));

        $dryRunOption = $definition->getOption('dry-run');
        $this->assertNull($dryRunOption->getShortcut());
        $this->assertEquals('模拟运行，只显示将被清理的会话，不实际执行', $dryRunOption->getDescription());
        $this->assertFalse($dryRunOption->isValueRequired());
        $this->assertFalse($dryRunOption->isValueOptional());
        $this->assertFalse($dryRunOption->isArray());

        // 测试dry-run选项的功能性
        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('模拟运行模式', $this->commandTester->getDisplay());
    }

    public function testOptionThreshold(): void
    {
        $command = self::getService(LearnSessionCleanupCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('threshold'));

        $thresholdOption = $definition->getOption('threshold');
        $this->assertNull($thresholdOption->getShortcut());
        $this->assertEquals('无效阈值（分钟）', $thresholdOption->getDescription());
        $this->assertTrue($thresholdOption->isValueRequired());
        $this->assertFalse($thresholdOption->isValueOptional());
        $this->assertFalse($thresholdOption->isArray());
        $this->assertEquals(3, $thresholdOption->getDefault()); // INACTIVE_THRESHOLD_MINUTES

        // 测试threshold选项的功能性 - 提供自定义阈值
        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
            '--threshold' => 5,
        ]);
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('查找 5 分钟内未更新的活跃学习会话', $this->commandTester->getDisplay());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(LearnSessionCleanupCommand::class);
        $this->assertInstanceOf(LearnSessionCleanupCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('train:learn-session:cleanup');
        $this->commandTester = new CommandTester($command);
    }
}
