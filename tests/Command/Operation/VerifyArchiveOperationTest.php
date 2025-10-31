<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Command\Operation\VerifyArchiveOperation;

/**
 * VerifyArchiveOperation 单元测试
 *
 * @internal
 */
#[CoversClass(VerifyArchiveOperation::class)]
#[RunTestsInSeparateProcesses]
final class VerifyArchiveOperationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 初始化代码
    }

    public function testServiceCanBeRetrievedFromContainer(): void
    {
        $service = self::getService(VerifyArchiveOperation::class);
        $this->assertInstanceOf(VerifyArchiveOperation::class, $service);
    }

    public function testExecuteWithSingleArchive(): void
    {
        $operation = self::getService(VerifyArchiveOperation::class);
        $io = $this->createMock(SymfonyStyle::class);

        $config = [
            'archiveId' => 'test-archive-id',
            'userId' => null,
            'courseId' => null,
            'batchSize' => 10,
        ];

        // 测试方法调用的行为，验证返回结果结构
        // operation应该优雅处理不存在的archiveId，返回结果而不是抛异常
        $result = $operation->execute($config, $io);

        // 验证返回结果包含期望的字段
        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('verified', $result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertArrayHasKey('warnings', $result);
    }

    public function testValidateConfig(): void
    {
        $operation = self::getService(VerifyArchiveOperation::class);

        // validateConfig 方法没有验证逻辑，不应抛出异常
        $operation->validateConfig([]);
        $operation->validateConfig(['archiveId' => 'test']);

        // 测试通过validateConfig不抛出异常 - void方法无返回值
        $this->expectNotToPerformAssertions();
    }
}
