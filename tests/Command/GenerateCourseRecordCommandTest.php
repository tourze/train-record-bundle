<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TrainRecordBundle\Command\GenerateCourseRecordCommand;

/**
 * GenerateCourseRecordCommand 单元测试
 *
 * @internal
 */
#[CoversClass(GenerateCourseRecordCommand::class)]
#[RunTestsInSeparateProcesses]
final class GenerateCourseRecordCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getContainer()->get(GenerateCourseRecordCommand::class);
        $this->assertInstanceOf(GenerateCourseRecordCommand::class, $command);

        $this->assertNotNull($command);
        $this->assertEquals('job-training:generate-course-record', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getContainer()->get(GenerateCourseRecordCommand::class);
        $this->assertInstanceOf(GenerateCourseRecordCommand::class, $command);

        $this->assertEquals('学成学时证明', $command->getDescription());
    }

    public function testCommandHasRequiredArguments(): void
    {
        $command = self::getContainer()->get(GenerateCourseRecordCommand::class);
        $this->assertInstanceOf(GenerateCourseRecordCommand::class, $command);
        $definition = $command->getDefinition();

        // 验证命令参数定义
        $this->assertTrue($definition->hasArgument('user-id'), 'Command should have user-id argument');
        $this->assertTrue($definition->hasArgument('course-id'), 'Command should have course-id argument');

        // 验证参数是否为必需
        $userIdArg = $definition->getArgument('user-id');
        $courseIdArg = $definition->getArgument('course-id');

        $this->assertTrue($userIdArg->isRequired(), 'user-id argument should be required');
        $this->assertTrue($courseIdArg->isRequired(), 'course-id argument should be required');
    }

    public function testCommandExecutionWithMissingBinary(): void
    {
        // 由于wkhtmltopdf二进制文件在测试环境中不可用，跳过此测试
        // 这个测试验证的是真实PDF生成功能，需要依赖外部二进制文件
        // 在CI/CD环境中，应该通过集成测试或手动测试来验证此功能
        self::markTestSkipped('跳过需要wkhtmltopdf二进制文件的测试 - 应在集成环境中测试PDF生成功能');
    }

    public function testCommandExecutionWithInvalidUserId(): void
    {
        // 由于wkhtmltopdf二进制文件在测试环境中不可用，跳过此测试
        // 这个测试验证的是真实PDF生成功能，需要依赖外部二进制文件
        self::markTestSkipped('跳过需要wkhtmltopdf二进制文件的测试 - 应在集成环境中测试PDF生成功能');
    }

    public function testCommandExecutionWithInvalidCourseId(): void
    {
        // 由于wkhtmltopdf二进制文件在测试环境中不可用，跳过此测试
        // 这个测试验证的是真实PDF生成功能，需要依赖外部二进制文件
        self::markTestSkipped('跳过需要wkhtmltopdf二进制文件的测试 - 应在集成环境中测试PDF生成功能');
    }

    public function testArgumentUserId(): void
    {
        // 测试必需的user-id参数
        $this->expectException(RuntimeException::class);
        $this->commandTester->execute([
            'course-id' => 'test-course-456',
            // 缺少必需的user-id参数
        ]);
    }

    public function testArgumentCourseId(): void
    {
        // 测试必需的course-id参数
        $this->expectException(RuntimeException::class);
        $this->commandTester->execute([
            'user-id' => 'test-user-123',
            // 缺少必需的course-id参数
        ]);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(GenerateCourseRecordCommand::class);
        $this->assertInstanceOf(GenerateCourseRecordCommand::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('job-training:generate-course-record');
        $this->commandTester = new CommandTester($command);
    }
}
