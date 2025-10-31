<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Command\Operation\ArchiveOperationFactory;
use Tourze\TrainRecordBundle\Command\Operation\CleanupArchiveOperation;
use Tourze\TrainRecordBundle\Command\Operation\CreateArchiveOperation;
use Tourze\TrainRecordBundle\Command\Operation\ExportArchiveOperation;
use Tourze\TrainRecordBundle\Command\Operation\UpdateArchiveOperation;
use Tourze\TrainRecordBundle\Command\Operation\VerifyArchiveOperation;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;

/**
 * ArchiveOperationFactory 单元测试
 *
 * @internal
 */
#[CoversClass(ArchiveOperationFactory::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveOperationFactoryTest extends AbstractIntegrationTestCase
{
    private ArchiveOperationFactory $factory;

    protected function onSetUp(): void
    {
        $this->factory = self::getService(ArchiveOperationFactory::class);
    }

    public function testFactoryCanBeInstantiated(): void
    {
        $this->assertNotNull($this->factory);
    }

    public function testCreateOperation(): void
    {
        $createOperation = $this->factory->createOperation('create');
        $this->assertInstanceOf(CreateArchiveOperation::class, $createOperation);

        $updateOperation = $this->factory->createOperation('update');
        $this->assertInstanceOf(UpdateArchiveOperation::class, $updateOperation);

        $verifyOperation = $this->factory->createOperation('verify');
        $this->assertInstanceOf(VerifyArchiveOperation::class, $verifyOperation);

        $exportOperation = $this->factory->createOperation('export');
        $this->assertInstanceOf(ExportArchiveOperation::class, $exportOperation);

        $cleanupOperation = $this->factory->createOperation('cleanup');
        $this->assertInstanceOf(CleanupArchiveOperation::class, $cleanupOperation);
    }

    public function testCreateOperationWithUnsupportedActionThrowsException(): void
    {
        $this->expectException(UnsupportedActionException::class);
        $this->expectExceptionMessage('不支持的操作类型: invalid');

        $this->factory->createOperation('invalid');
    }
}
