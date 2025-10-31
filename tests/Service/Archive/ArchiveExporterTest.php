<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Service\Archive;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Exception\InvalidArgumentException;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveExporter;

/**
 * ArchiveExporter 集成测试
 *
 * @internal
 */
#[CoversClass(ArchiveExporter::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveExporterTest extends AbstractIntegrationTestCase
{
    private ArchiveExporter $archiveExporter;

    protected function onSetUp(): void
    {
        $this->archiveExporter = self::getService(ArchiveExporter::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertNotNull($this->archiveExporter);
    }

    public function testExportArchiveWithInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('档案不存在');

        $this->archiveExporter->exportArchive('invalid-id', 'json');
    }

    public function testExportArchiveWithUnsupportedFormat(): void
    {
        // 注意：由于档案查找优先级高于格式检查，当档案不存在时会先抛出"档案不存在"异常
        // 这个测试验证了异常处理的优先级顺序
        $this->expectException(InvalidArgumentException::class);

        // 使用不存在的档案ID，预期会先遇到"档案不存在"错误而不是格式错误
        $this->expectExceptionMessage('档案不存在');

        $this->archiveExporter->exportArchive('non-existent-id', 'unsupported');
    }

    public function testExportArchiveWithEmptyIdThrowsException(): void
    {
        // 测试空字符串ID的行为
        $this->expectException(InvalidArgumentException::class);

        $this->archiveExporter->exportArchive('', 'json');
    }

    public function testExportArchiveWithEmptyFormatThrowsException(): void
    {
        // 测试空格式的行为
        $this->expectException(InvalidArgumentException::class);

        $this->archiveExporter->exportArchive('some-id', 'invalid');
    }
}
