<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

/**
 * ArchiveStatus 枚举测试
 */
class ArchiveStatusTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('active', ArchiveStatus::ACTIVE->value);
        $this->assertEquals('archived', ArchiveStatus::ARCHIVED->value);
        $this->assertEquals('expired', ArchiveStatus::EXPIRED->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('活跃', ArchiveStatus::ACTIVE->getLabel());
        $this->assertEquals('已归档', ArchiveStatus::ARCHIVED->getLabel());
        $this->assertEquals('已过期', ArchiveStatus::EXPIRED->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('档案处于活跃状态，可以继续记录', ArchiveStatus::ACTIVE->getDescription());
        $this->assertEquals('档案已归档，数据已压缩存储', ArchiveStatus::ARCHIVED->getDescription());
        $this->assertEquals('档案已过期，可以清理', ArchiveStatus::EXPIRED->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function test_get_color(): void
    {
        $this->assertEquals('green', ArchiveStatus::ACTIVE->getColor());
        $this->assertEquals('blue', ArchiveStatus::ARCHIVED->getColor());
        $this->assertEquals('red', ArchiveStatus::EXPIRED->getColor());
    }

    /**
     * 测试是否可以归档
     */
    public function test_can_archive(): void
    {
        $this->assertTrue(ArchiveStatus::ACTIVE->canArchive());
        $this->assertFalse(ArchiveStatus::ARCHIVED->canArchive());
        $this->assertFalse(ArchiveStatus::EXPIRED->canArchive());
    }

    /**
     * 测试是否已归档
     */
    public function test_is_archived(): void
    {
        $this->assertFalse(ArchiveStatus::ACTIVE->isArchived());
        $this->assertTrue(ArchiveStatus::ARCHIVED->isArchived());
        $this->assertFalse(ArchiveStatus::EXPIRED->isArchived());
    }

    /**
     * 测试是否已过期
     */
    public function test_is_expired(): void
    {
        $this->assertFalse(ArchiveStatus::ACTIVE->isExpired());
        $this->assertFalse(ArchiveStatus::ARCHIVED->isExpired());
        $this->assertTrue(ArchiveStatus::EXPIRED->isExpired());
    }

    /**
     * 测试获取所有状态
     */
    public function test_get_all_statuses(): void
    {
        $statuses = ArchiveStatus::getAllStatuses();
        
        $this->assertCount(3, $statuses);
        $this->assertContains(ArchiveStatus::ACTIVE, $statuses);
        $this->assertContains(ArchiveStatus::ARCHIVED, $statuses);
        $this->assertContains(ArchiveStatus::EXPIRED, $statuses);
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = ArchiveStatus::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContains(ArchiveStatus::ACTIVE, $cases);
        $this->assertContains(ArchiveStatus::ARCHIVED, $cases);
        $this->assertContains(ArchiveStatus::EXPIRED, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(ArchiveStatus::ACTIVE, ArchiveStatus::from('active'));
        $this->assertEquals(ArchiveStatus::ARCHIVED, ArchiveStatus::from('archived'));
        $this->assertEquals(ArchiveStatus::EXPIRED, ArchiveStatus::from('expired'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(ArchiveStatus::ACTIVE, ArchiveStatus::tryFrom('active'));
        $this->assertEquals(ArchiveStatus::ARCHIVED, ArchiveStatus::tryFrom('archived'));
        $this->assertEquals(ArchiveStatus::EXPIRED, ArchiveStatus::tryFrom('expired'));
        $this->assertNull(ArchiveStatus::tryFrom('invalid'));
    }

    /**
     * 测试状态转换逻辑
     */
    public function test_status_transitions(): void
    {
        // 活跃状态可以归档
        $active = ArchiveStatus::ACTIVE;
        $this->assertTrue($active->canArchive());
        $this->assertFalse($active->isArchived());
        $this->assertFalse($active->isExpired());
        
        // 已归档状态不能再归档
        $archived = ArchiveStatus::ARCHIVED;
        $this->assertFalse($archived->canArchive());
        $this->assertTrue($archived->isArchived());
        $this->assertFalse($archived->isExpired());
        
        // 已过期状态不能归档
        $expired = ArchiveStatus::EXPIRED;
        $this->assertFalse($expired->canArchive());
        $this->assertFalse($expired->isArchived());
        $this->assertTrue($expired->isExpired());
    }
}