<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Command\Operation\ExportArchiveOperation;
use Tourze\TrainRecordBundle\Exception\ArgumentException;

/**
 * ExportArchiveOperation 单元测试
 *
 * @internal
 */
#[CoversClass(ExportArchiveOperation::class)]
#[RunTestsInSeparateProcesses]
final class ExportArchiveOperationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 初始化代码
    }

    public function testServiceCanBeRetrievedFromContainer(): void
    {
        $service = self::getService(ExportArchiveOperation::class);
        $this->assertInstanceOf(ExportArchiveOperation::class, $service);
    }

    public function testExecuteWithDryRun(): void
    {
        $operation = self::getService(ExportArchiveOperation::class);
        $io = $this->createMock(SymfonyStyle::class);

        $config = [
            'archiveId' => 'test-archive-id',
            'format' => 'json',
            'dryRun' => true,
        ];

        $result = $operation->execute($config, $io);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('exported', $result);
        $this->assertSame('档案导出完成', $result['message']);
        $this->assertSame(1, $result['exported']);
    }

    public function testValidateConfigWithMissingArchiveId(): void
    {
        $operation = self::getService(ExportArchiveOperation::class);

        $this->expectException(ArgumentException::class);
        $this->expectExceptionMessage('导出操作需要指定档案ID');

        $operation->validateConfig(['archiveId' => null]);
    }

    public function testValidateConfigWithValidConfig(): void
    {
        $operation = self::getService(ExportArchiveOperation::class);

        // 不应抛出异常
        $operation->validateConfig(['archiveId' => 'valid-archive-id']);

        // 测试通过validateConfig不抛出异常 - void方法无返回值
        $this->expectNotToPerformAssertions();
    }
}
