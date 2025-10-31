<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Command\Operation\CleanupArchiveOperation;

/**
 * CleanupArchiveOperation 单元测试
 *
 * @internal
 */
#[CoversClass(CleanupArchiveOperation::class)]
#[RunTestsInSeparateProcesses]
final class CleanupArchiveOperationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testOperationCanBeInstantiated(): void
    {
        $operation = self::getService(CleanupArchiveOperation::class);

        $this->assertNotNull($operation);
    }

    public function testValidateConfigWithValidParameters(): void
    {
        $operation = self::getService(CleanupArchiveOperation::class);

        // 测试各种有效的参数 - 方法没有特殊验证逻辑，不应抛出异常
        $validConfigs = [
            ['daysBeforeExpiry' => 30],
            ['daysBeforeExpiry' => 7],
            ['daysBeforeExpiry' => 90],
            ['daysBeforeExpiry' => 1],
            [], // 空配置也应该被接受
        ];

        // 所有配置都不应抛出异常
        $this->expectNotToPerformAssertions();

        foreach ($validConfigs as $config) {
            $operation->validateConfig($config);
        }
    }

    public function testExecute(): void
    {
        $operation = self::getService(CleanupArchiveOperation::class);
        // 使用具体类 SymfonyStyle 而不是接口，因为：
        // 1) SymfonyStyle 没有对应的接口，它是 Symfony Console 组件的具体实现类
        // 2) 在测试环境中，我们需要模拟其输出方法，这是合理且必要的
        // 3) 没有更好的替代方案，因为业务代码直接依赖 SymfonyStyle 类
        $io = $this->createMock(SymfonyStyle::class);

        // 配置测试数据
        $config = [
            'daysBeforeExpiry' => 30,
            'dryRun' => true,
        ];

        // 执行方法
        $result = $operation->execute($config, $io);

        // 验证返回结果结构
        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('cleaned', $result);
        $this->assertArrayHasKey('expiring', $result);

        // 验证消息格式
        // message字段已确定为字符串类型，无需重复检查
        $this->assertIsInt($result['cleaned']);
        $this->assertIsInt($result['expiring']);
    }
}
