<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;

/**
 * ArchiveFormat 枚举测试
 */
class ArchiveFormatTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('json', ArchiveFormat::JSON->value);
        $this->assertEquals('xml', ArchiveFormat::XML->value);
        $this->assertEquals('pdf', ArchiveFormat::PDF->value);
        $this->assertEquals('zip', ArchiveFormat::ZIP->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('JSON', ArchiveFormat::JSON->getLabel());
        $this->assertEquals('XML', ArchiveFormat::XML->getLabel());
        $this->assertEquals('PDF', ArchiveFormat::PDF->getLabel());
        $this->assertEquals('ZIP', ArchiveFormat::ZIP->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('JSON格式，便于程序处理', ArchiveFormat::JSON->getDescription());
        $this->assertEquals('XML格式，结构化数据', ArchiveFormat::XML->getDescription());
        $this->assertEquals('PDF格式，便于阅读和打印', ArchiveFormat::PDF->getDescription());
        $this->assertEquals('ZIP压缩格式，节省存储空间', ArchiveFormat::ZIP->getDescription());
    }

    /**
     * 测试获取文件扩展名
     */
    public function test_get_extension(): void
    {
        $this->assertEquals('.json', ArchiveFormat::JSON->getExtension());
        $this->assertEquals('.xml', ArchiveFormat::XML->getExtension());
        $this->assertEquals('.pdf', ArchiveFormat::PDF->getExtension());
        $this->assertEquals('.zip', ArchiveFormat::ZIP->getExtension());
    }

    /**
     * 测试获取 MIME 类型
     */
    public function test_get_mime_type(): void
    {
        $this->assertEquals('application/json', ArchiveFormat::JSON->getMimeType());
        $this->assertEquals('application/xml', ArchiveFormat::XML->getMimeType());
        $this->assertEquals('application/pdf', ArchiveFormat::PDF->getMimeType());
        $this->assertEquals('application/zip', ArchiveFormat::ZIP->getMimeType());
    }

    /**
     * 测试是否支持压缩
     */
    public function test_supports_compression(): void
    {
        $this->assertTrue(ArchiveFormat::JSON->supportsCompression());
        $this->assertTrue(ArchiveFormat::XML->supportsCompression());
        $this->assertFalse(ArchiveFormat::PDF->supportsCompression());
        $this->assertTrue(ArchiveFormat::ZIP->supportsCompression());
    }

    /**
     * 测试是否为二进制格式
     */
    public function test_is_binary(): void
    {
        $this->assertFalse(ArchiveFormat::JSON->isBinary());
        $this->assertFalse(ArchiveFormat::XML->isBinary());
        $this->assertTrue(ArchiveFormat::PDF->isBinary());
        $this->assertTrue(ArchiveFormat::ZIP->isBinary());
    }

    /**
     * 测试是否为文本格式
     */
    public function test_is_text(): void
    {
        $this->assertTrue(ArchiveFormat::JSON->isText());
        $this->assertTrue(ArchiveFormat::XML->isText());
        $this->assertFalse(ArchiveFormat::PDF->isText());
        $this->assertFalse(ArchiveFormat::ZIP->isText());
    }

    /**
     * 测试推荐的压缩级别
     */
    public function test_get_recommended_compression_level(): void
    {
        $this->assertEquals(6, ArchiveFormat::JSON->getRecommendedCompressionLevel());
        $this->assertEquals(6, ArchiveFormat::XML->getRecommendedCompressionLevel());
        $this->assertEquals(3, ArchiveFormat::PDF->getRecommendedCompressionLevel());
        $this->assertEquals(9, ArchiveFormat::ZIP->getRecommendedCompressionLevel());
    }

    /**
     * 测试获取所有格式
     */
    public function test_get_all_formats(): void
    {
        $formats = ArchiveFormat::getAllFormats();
        
        $this->assertCount(4, $formats);
        $this->assertContains(ArchiveFormat::JSON, $formats);
        $this->assertContains(ArchiveFormat::XML, $formats);
        $this->assertContains(ArchiveFormat::PDF, $formats);
        $this->assertContains(ArchiveFormat::ZIP, $formats);
    }

    /**
     * 测试获取文本格式
     */
    public function test_get_text_formats(): void
    {
        $formats = ArchiveFormat::getTextFormats();
        
        $this->assertCount(2, $formats);
        $this->assertContains(ArchiveFormat::JSON, $formats);
        $this->assertContains(ArchiveFormat::XML, $formats);
        $this->assertNotContains(ArchiveFormat::PDF, $formats);
        $this->assertNotContains(ArchiveFormat::ZIP, $formats);
    }

    /**
     * 测试获取二进制格式
     */
    public function test_get_binary_formats(): void
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
    public function test_from_extension(): void
    {
        // 不带点的扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('json'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('xml'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::fromExtension('pdf'));
        $this->assertEquals(ArchiveFormat::ZIP, ArchiveFormat::fromExtension('zip'));
        
        // 带点的扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('.json'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('.xml'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::fromExtension('.pdf'));
        $this->assertEquals(ArchiveFormat::ZIP, ArchiveFormat::fromExtension('.zip'));
        
        // 大写扩展名
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::fromExtension('JSON'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::fromExtension('.XML'));
        
        // 无效扩展名
        $this->assertNull(ArchiveFormat::fromExtension('txt'));
        $this->assertNull(ArchiveFormat::fromExtension('.doc'));
        $this->assertNull(ArchiveFormat::fromExtension(''));
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = ArchiveFormat::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(ArchiveFormat::JSON, $cases);
        $this->assertContains(ArchiveFormat::XML, $cases);
        $this->assertContains(ArchiveFormat::PDF, $cases);
        $this->assertContains(ArchiveFormat::ZIP, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::from('json'));
        $this->assertEquals(ArchiveFormat::XML, ArchiveFormat::from('xml'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::from('pdf'));
        $this->assertEquals(ArchiveFormat::ZIP, ArchiveFormat::from('zip'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(ArchiveFormat::JSON, ArchiveFormat::tryFrom('json'));
        $this->assertEquals(ArchiveFormat::PDF, ArchiveFormat::tryFrom('pdf'));
        $this->assertNull(ArchiveFormat::tryFrom('invalid'));
    }
}