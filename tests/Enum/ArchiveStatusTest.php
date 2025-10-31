<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

/**
 * ArchiveStatus 枚举测试
 *
 * @internal
 */
#[CoversClass(ArchiveStatus::class)]
#[RunTestsInSeparateProcesses]
final class ArchiveStatusTest extends AbstractEnumTestCase
{
    public function testEnumCasesExist(): void
    {
        $cases = ArchiveStatus::cases();

        self::assertCount(6, $cases);
        self::assertContainsOnlyInstancesOf(ArchiveStatus::class, $cases);
    }

    #[TestWith([ArchiveStatus::ACTIVE, 'active', '活跃'])]
    #[TestWith([ArchiveStatus::PROCESSING, 'processing', '处理中'])]
    #[TestWith([ArchiveStatus::COMPLETED, 'completed', '已完成'])]
    #[TestWith([ArchiveStatus::FAILED, 'failed', '失败'])]
    #[TestWith([ArchiveStatus::ARCHIVED, 'archived', '已归档'])]
    #[TestWith([ArchiveStatus::EXPIRED, 'expired', '已过期'])]
    public function testValueAndLabel(ArchiveStatus $enum, string $expectedValue, string $expectedLabel): void
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
        $this->assertEquals('档案处于活跃状态，可以继续记录', ArchiveStatus::ACTIVE->getDescription());
        $this->assertEquals('档案正在处理中', ArchiveStatus::PROCESSING->getDescription());
        $this->assertEquals('档案处理已完成', ArchiveStatus::COMPLETED->getDescription());
        $this->assertEquals('档案处理失败', ArchiveStatus::FAILED->getDescription());
        $this->assertEquals('档案已归档，数据已压缩存储', ArchiveStatus::ARCHIVED->getDescription());
        $this->assertEquals('档案已过期，可以清理', ArchiveStatus::EXPIRED->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function testGetColor(): void
    {
        $this->assertEquals('green', ArchiveStatus::ACTIVE->getColor());
        $this->assertEquals('gray', ArchiveStatus::ARCHIVED->getColor());
        $this->assertEquals('darkred', ArchiveStatus::EXPIRED->getColor());
    }

    /**
     * 测试是否可以归档
     */
    public function testCanArchive(): void
    {
        $this->assertTrue(ArchiveStatus::ACTIVE->canArchive());
        $this->assertFalse(ArchiveStatus::ARCHIVED->canArchive());
        $this->assertFalse(ArchiveStatus::EXPIRED->canArchive());
    }

    /**
     * 测试是否已归档
     */
    public function testIsArchived(): void
    {
        $this->assertFalse(ArchiveStatus::ACTIVE->isArchived());
        $this->assertTrue(ArchiveStatus::ARCHIVED->isArchived());
        $this->assertFalse(ArchiveStatus::EXPIRED->isArchived());
    }

    /**
     * 测试是否已过期
     */
    public function testIsExpired(): void
    {
        $this->assertFalse(ArchiveStatus::ACTIVE->isExpired());
        $this->assertFalse(ArchiveStatus::ARCHIVED->isExpired());
        $this->assertTrue(ArchiveStatus::EXPIRED->isExpired());
    }

    /**
     * 测试获取所有状态
     */
    public function testGetAllStatuses(): void
    {
        $statuses = ArchiveStatus::getAllStatuses();

        $this->assertCount(3, $statuses);
        $this->assertContains(ArchiveStatus::ACTIVE, $statuses);
        $this->assertContains(ArchiveStatus::ARCHIVED, $statuses);
        $this->assertContains(ArchiveStatus::EXPIRED, $statuses);
    }

    public function testFromWithValidValue(): void
    {
        self::assertSame(ArchiveStatus::ACTIVE, ArchiveStatus::from('active'));
        self::assertSame(ArchiveStatus::PROCESSING, ArchiveStatus::from('processing'));
        self::assertSame(ArchiveStatus::COMPLETED, ArchiveStatus::from('completed'));
        self::assertSame(ArchiveStatus::FAILED, ArchiveStatus::from('failed'));
        self::assertSame(ArchiveStatus::ARCHIVED, ArchiveStatus::from('archived'));
        self::assertSame(ArchiveStatus::EXPIRED, ArchiveStatus::from('expired'));
    }

    public function testTryFromWithValidValue(): void
    {
        self::assertSame(ArchiveStatus::ACTIVE, ArchiveStatus::tryFrom('active'));
        self::assertSame(ArchiveStatus::PROCESSING, ArchiveStatus::tryFrom('processing'));
        self::assertSame(ArchiveStatus::COMPLETED, ArchiveStatus::tryFrom('completed'));
        self::assertSame(ArchiveStatus::FAILED, ArchiveStatus::tryFrom('failed'));
        self::assertSame(ArchiveStatus::ARCHIVED, ArchiveStatus::tryFrom('archived'));
        self::assertSame(ArchiveStatus::EXPIRED, ArchiveStatus::tryFrom('expired'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (ArchiveStatus $case) => $case->value, ArchiveStatus::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (ArchiveStatus $case) => $case->getLabel(), ArchiveStatus::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels must be unique');
    }

    /**
     * 测试状态转换逻辑
     */
    public function testStatusTransitions(): void
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

    public function testToSelectItemReturnsCorrectFormat(): void
    {
        $selectItem = ArchiveStatus::ACTIVE->toSelectItem();

        self::assertIsArray($selectItem);
        self::assertCount(4, $selectItem);
        self::assertArrayHasKey('value', $selectItem);
        self::assertArrayHasKey('label', $selectItem);
        self::assertArrayHasKey('text', $selectItem);
        self::assertArrayHasKey('name', $selectItem);

        self::assertSame('active', $selectItem['value']);
        self::assertSame('活跃', $selectItem['label']);
        self::assertSame('活跃', $selectItem['text']);
        self::assertSame('活跃', $selectItem['name']);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = ArchiveStatus::ACTIVE->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('active', $result['value']);
        $this->assertEquals('活跃', $result['label']);
    }
}
