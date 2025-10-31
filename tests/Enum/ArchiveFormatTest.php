<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;

/**
 * ArchiveFormat 枚举测试
 *
 * @internal
 */
#[CoversClass(ArchiveFormat::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveFormatTest extends AbstractEnumTestCase
{
    public function testEnumCasesExist(): void
    {
        $cases = ArchiveFormat::cases();

        self::assertCount(5, $cases);
        self::assertContainsOnlyInstancesOf(ArchiveFormat::class, $cases);
    }

    #[TestWith([ArchiveFormat::JSON, 'json', 'JSON'])]
    #[TestWith([ArchiveFormat::XML, 'xml', 'XML'])]
    #[TestWith([ArchiveFormat::PDF, 'pdf', 'PDF'])]
    #[TestWith([ArchiveFormat::ZIP, 'zip', 'ZIP'])]
    #[TestWith([ArchiveFormat::CSV, 'csv', 'CSV'])]
    public function testValueAndLabel(ArchiveFormat $enum, string $expectedValue, string $expectedLabel): void
    {
        self::assertSame($expectedValue, $enum->value);
        self::assertSame($expectedLabel, $enum->getLabel());

        // Test toArray format
        $array = $enum->toArray();
        self::assertIsArray($array);
        self::assertCount(2, $array);
        self::assertArrayHasKey('value', $array);
        self::assertArrayHasKey('label', $array);
        self::assertSame($expectedValue, $array['value']);
        self::assertSame($expectedLabel, $array['label']);
    }

    /**
     * 测试获取描述
     */
    public function testGetDescription(): void
    {
        $this->assertEquals('JSON格式，便于程序处理', ArchiveFormat::JSON->getDescription());
        $this->assertEquals('XML格式，结构化数据', ArchiveFormat::XML->getDescription());
        $this->assertEquals('PDF格式，便于阅读和打印', ArchiveFormat::PDF->getDescription());
        $this->assertEquals('ZIP压缩格式，节省存储空间', ArchiveFormat::ZIP->getDescription());
        $this->assertEquals('CSV格式，便于数据分析', ArchiveFormat::CSV->getDescription());
    }

    /**
     * 测试获取文件扩展名
     */
    public function testGetExtension(): void
    {
        $this->assertEquals('.json', ArchiveFormat::JSON->getExtension());
        $this->assertEquals('.xml', ArchiveFormat::XML->getExtension());
        $this->assertEquals('.pdf', ArchiveFormat::PDF->getExtension());
        $this->assertEquals('.zip', ArchiveFormat::ZIP->getExtension());
        $this->assertEquals('.csv', ArchiveFormat::CSV->getExtension());
    }

    /**
     * 测试获取 MIME 类型
     */
    public function testGetMimeType(): void
    {
        $this->assertEquals('application/json', ArchiveFormat::JSON->getMimeType());
        $this->assertEquals('application/xml', ArchiveFormat::XML->getMimeType());
        $this->assertEquals('application/pdf', ArchiveFormat::PDF->getMimeType());
        $this->assertEquals('application/zip', ArchiveFormat::ZIP->getMimeType());
        $this->assertEquals('text/csv', ArchiveFormat::CSV->getMimeType());
    }

    /**
     * 测试是否支持压缩
     */
    public function testSupportsCompression(): void
    {
        $this->assertTrue(ArchiveFormat::JSON->supportsCompression());
        $this->assertTrue(ArchiveFormat::XML->supportsCompression());
        $this->assertFalse(ArchiveFormat::PDF->supportsCompression());
        $this->assertTrue(ArchiveFormat::ZIP->supportsCompression());
        $this->assertTrue(ArchiveFormat::CSV->supportsCompression());
    }

    /**
     * 测试是否为二进制格式
     */
    public function testIsBinary(): void
    {
        $this->assertFalse(ArchiveFormat::JSON->isBinary());
        $this->assertFalse(ArchiveFormat::XML->isBinary());
        $this->assertTrue(ArchiveFormat::PDF->isBinary());
        $this->assertTrue(ArchiveFormat::ZIP->isBinary());
        $this->assertFalse(ArchiveFormat::CSV->isBinary());
    }

    /**
     * 测试是否为文本格式
     */
    public function testIsText(): void
    {
        $this->assertTrue(ArchiveFormat::JSON->isText());
        $this->assertTrue(ArchiveFormat::XML->isText());
        $this->assertFalse(ArchiveFormat::PDF->isText());
        $this->assertFalse(ArchiveFormat::ZIP->isText());
        $this->assertTrue(ArchiveFormat::CSV->isText());
    }

    /**
     * 测试推荐的压缩级别
     */
    public function testGetRecommendedCompressionLevel(): void
    {
        $this->assertEquals(6, ArchiveFormat::JSON->getRecommendedCompressionLevel());
        $this->assertEquals(6, ArchiveFormat::XML->getRecommendedCompressionLevel());
        $this->assertEquals(3, ArchiveFormat::PDF->getRecommendedCompressionLevel());
        $this->assertEquals(9, ArchiveFormat::ZIP->getRecommendedCompressionLevel());
        $this->assertEquals(6, ArchiveFormat::CSV->getRecommendedCompressionLevel());
    }

    /**
     * 测试获取所有格式
     */
    public function testGetAllFormats(): void
    {
        $formats = ArchiveFormat::getAllFormats();

        $this->assertCount(5, $formats);
        $this->assertContains(ArchiveFormat::JSON, $formats);
        $this->assertContains(ArchiveFormat::XML, $formats);
        $this->assertContains(ArchiveFormat::PDF, $formats);
        $this->assertContains(ArchiveFormat::ZIP, $formats);
        $this->assertContains(ArchiveFormat::CSV, $formats);
    }

    /**
     * 测试获取文本格式
     */
    public function testGetTextFormats(): void
    {
        $formats = ArchiveFormat::getTextFormats();

        $this->assertCount(3, $formats);
        $this->assertContains(ArchiveFormat::JSON, $formats);
        $this->assertContains(ArchiveFormat::XML, $formats);
        $this->assertContains(ArchiveFormat::CSV, $formats);
        $this->assertNotContains(ArchiveFormat::PDF, $formats);
        $this->assertNotContains(ArchiveFormat::ZIP, $formats);
    }

    /**
     * 测试获取二进制格式
     */
    public function testGetBinaryFormats(): void
    {
        $formats = ArchiveFormat::getBinaryFormats();

        $this->assertCount(2, $formats);
        $this->assertContains(ArchiveFormat::PDF, $formats);
        $this->assertContains(ArchiveFormat::ZIP, $formats);
        $this->assertNotContains(ArchiveFormat::JSON, $formats);
        $this->assertNotContains(ArchiveFormat::XML, $formats);
    }

    /**
     * 测试从文件扩展名创建
     */
    public function testFromExtension(): void
    {
        // 不带点的扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('json'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('xml'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::fromExtension('pdf'));
        $this->assertEquals(ArchiveFormat::ZIP, ArchiveFormat::fromExtension('zip'));
        $this->assertEquals(ArchiveFormat::CSV, ArchiveFormat::fromExtension('csv'));

        // 带点的扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('.json'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('.xml'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::fromExtension('.pdf'));
        $this->assertEquals(ArchiveFormat::ZIP, ArchiveFormat::fromExtension('.zip'));
        $this->assertEquals(ArchiveFormat::CSV, ArchiveFormat::fromExtension('.csv'));

        // 大写扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('JSON'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('.XML'));
        $this->assertEquals(ArchiveFormat::CSV, ArchiveFormat::fromExtension('CSV'));

        // 无效扩展名
        $this->assertNull(ArchiveFormat::fromExtension('txt'));
        $this->assertNull(ArchiveFormat::fromExtension('.doc'));
        $this->assertNull(ArchiveFormat::fromExtension(''));
    }

    public function testFromWithValidValue(): void
    {
        self::assertSame(ArchiveFormat::JSON, ArchiveFormat::from('json'));
        self::assertSame(ArchiveFormat::XML, ArchiveFormat::from('xml'));
        self::assertSame(ArchiveFormat::PDF, ArchiveFormat::from('pdf'));
        self::assertSame(ArchiveFormat::ZIP, ArchiveFormat::from('zip'));
        self::assertSame(ArchiveFormat::CSV, ArchiveFormat::from('csv'));
    }

    public function testTryFromWithValidValue(): void
    {
        self::assertSame(ArchiveFormat::JSON, ArchiveFormat::tryFrom('json'));
        self::assertSame(ArchiveFormat::XML, ArchiveFormat::tryFrom('xml'));
        self::assertSame(ArchiveFormat::PDF, ArchiveFormat::tryFrom('pdf'));
        self::assertSame(ArchiveFormat::ZIP, ArchiveFormat::tryFrom('zip'));
        self::assertSame(ArchiveFormat::CSV, ArchiveFormat::tryFrom('csv'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (ArchiveFormat $case) => $case->value, ArchiveFormat::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (ArchiveFormat $case) => $case->getLabel(), ArchiveFormat::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels must be unique');
    }

    public function testToSelectItemReturnsCorrectFormat(): void
    {
        $selectItem = ArchiveFormat::JSON->toSelectItem();

        self::assertIsArray($selectItem);
        self::assertCount(4, $selectItem);
        self::assertArrayHasKey('value', $selectItem);
        self::assertArrayHasKey('label', $selectItem);
        self::assertArrayHasKey('text', $selectItem);
        self::assertArrayHasKey('name', $selectItem);

        self::assertSame('json', $selectItem['value']);
        self::assertSame('JSON', $selectItem['label']);
        self::assertSame('JSON', $selectItem['text']);
        self::assertSame('JSON', $selectItem['name']);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = ArchiveFormat::JSON->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('json', $result['value']);
        $this->assertEquals('JSON', $result['label']);
    }
}
