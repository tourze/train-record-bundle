<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Command\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Command\Operation\CreateArchiveOperation;
use Tourze\TrainRecordBundle\Exception\InvalidArgumentException;

/**
 * CreateArchiveOperation 单元测试
 *
 * @internal
 */
#[CoversClass(CreateArchiveOperation::class)]
#[RunTestsInSeparateProcesses]
final class CreateArchiveOperationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testOperationCanBeInstantiated(): void
    {
        $operation = self::getService(CreateArchiveOperation::class);

        $this->assertNotNull($operation);
    }

    public function testValidateConfigPassesWithValidFormat(): void
    {
        $operation = self::getService(CreateArchiveOperation::class);

        // 测试所有有效的格式 - 不应抛出异常
        $validFormats = ['json', 'xml', 'csv'];

        $this->expectNotToPerformAssertions();

        foreach ($validFormats as $format) {
            $operation->validateConfig(['format' => $format]);
        }
    }

    public function testValidateConfigThrowsExceptionWithInvalidFormat(): void
    {
        $operation = self::getService(CreateArchiveOperation::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的归档格式');

        $operation->validateConfig(['format' => 'invalid']);
    }

    public function testValidateConfigThrowsExceptionWithMissingFormat(): void
    {
        $operation = self::getService(CreateArchiveOperation::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的归档格式');

        $operation->validateConfig([]);
    }

    public function testExecute(): void
    {
        $operation = self::getService(CreateArchiveOperation::class);
        // 使用具体类 SymfonyStyle 而不是接口，因为：
        // 1) SymfonyStyle 没有对应的接口，它是 Symfony Console 组件的具体实现类
        // 2) 在测试环境中，我们需要模拟其输出方法，这是合理且必要的
        // 3) 没有更好的替代方案，因为业务代码直接依赖 SymfonyStyle 类
        $io = $this->createMock(SymfonyStyle::class);

        $io->expects($this->once())->method('section')->with('创建学习档案');

        $config = [
            'format' => 'json',
            'userId' => 'test-user-123',
            'courseId' => 'test-course-456',
            'batchSize' => 100,
            'dryRun' => true,
        ];

        $result = $operation->execute($config, $io);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        // message字段已确定为字符串类型，无需重复检查
        $this->assertIsNumeric($result['created']);
        $this->assertIsNumeric($result['errors']);
    }
}
