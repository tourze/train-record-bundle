<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;

/**
 * AnomalySeverity 枚举测试
 */
class AnomalySeverityTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('low', AnomalySeverity::LOW->value);
        $this->assertEquals('medium', AnomalySeverity::MEDIUM->value);
        $this->assertEquals('high', AnomalySeverity::HIGH->value);
        $this->assertEquals('critical', AnomalySeverity::CRITICAL->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('低', AnomalySeverity::LOW->getLabel());
        $this->assertEquals('中', AnomalySeverity::MEDIUM->getLabel());
        $this->assertEquals('高', AnomalySeverity::HIGH->getLabel());
        $this->assertEquals('严重', AnomalySeverity::CRITICAL->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('轻微异常，可延后处理', AnomalySeverity::LOW->getDescription());
        $this->assertEquals('一般异常，需要关注', AnomalySeverity::MEDIUM->getDescription());
        $this->assertEquals('重要异常，需要及时处理', AnomalySeverity::HIGH->getDescription());
        $this->assertEquals('严重异常，需要立即处理', AnomalySeverity::CRITICAL->getDescription());
    }

    /**
     * 测试获取颜色
     */
    public function test_get_color(): void
    {
        $this->assertEquals('green', AnomalySeverity::LOW->getColor());
        $this->assertEquals('yellow', AnomalySeverity::MEDIUM->getColor());
        $this->assertEquals('orange', AnomalySeverity::HIGH->getColor());
        $this->assertEquals('red', AnomalySeverity::CRITICAL->getColor());
    }

    /**
     * 测试获取权重
     */
    public function test_get_weight(): void
    {
        $this->assertEquals(1, AnomalySeverity::LOW->getWeight());
        $this->assertEquals(2, AnomalySeverity::MEDIUM->getWeight());
        $this->assertEquals(3, AnomalySeverity::HIGH->getWeight());
        $this->assertEquals(4, AnomalySeverity::CRITICAL->getWeight());
    }

    /**
     * 测试是否需要立即处理
     */
    public function test_requires_immediate_action(): void
    {
        $this->assertFalse(AnomalySeverity::LOW->requiresImmediateAction());
        $this->assertFalse(AnomalySeverity::MEDIUM->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::HIGH->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::CRITICAL->requiresImmediateAction());
    }

    /**
     * 测试是否为高优先级
     */
    public function test_is_high_priority(): void
    {
        $this->assertFalse(AnomalySeverity::LOW->isHighPriority());
        $this->assertFalse(AnomalySeverity::MEDIUM->isHighPriority());
        $this->assertTrue(AnomalySeverity::HIGH->isHighPriority());
        $this->assertTrue(AnomalySeverity::CRITICAL->isHighPriority());
    }

    /**
     * 测试处理时限
     */
    public function test_get_processing_time_limit(): void
    {
        $this->assertEquals(72, AnomalySeverity::LOW->getProcessingTimeLimit());
        $this->assertEquals(24, AnomalySeverity::MEDIUM->getProcessingTimeLimit());
        $this->assertEquals(4, AnomalySeverity::HIGH->getProcessingTimeLimit());
        $this->assertEquals(1, AnomalySeverity::CRITICAL->getProcessingTimeLimit());
    }

    /**
     * 测试获取所有严重程度
     */
    public function test_get_all_severities(): void
    {
        $severities = AnomalySeverity::getAllSeverities();
        
        $this->assertCount(4, $severities);
        $this->assertContains(AnomalySeverity::LOW, $severities);
        $this->assertContains(AnomalySeverity::MEDIUM, $severities);
        $this->assertContains(AnomalySeverity::HIGH, $severities);
        $this->assertContains(AnomalySeverity::CRITICAL, $severities);
    }

    /**
     * 测试按权重排序
     */
    public function test_get_sorted_by_weight(): void
    {
        $sorted = AnomalySeverity::getSortedByWeight();
        
        $this->assertCount(4, $sorted);
        $this->assertEquals(AnomalySeverity::CRITICAL, $sorted[0]);
        $this->assertEquals(AnomalySeverity::HIGH, $sorted[1]);
        $this->assertEquals(AnomalySeverity::MEDIUM, $sorted[2]);
        $this->assertEquals(AnomalySeverity::LOW, $sorted[3]);
    }

    /**
     * 测试从字符串创建
     */
    public function test_from_string(): void
    {
        // 英文
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('low'));
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalySeverity::fromString('medium'));
        $this->assertEquals(AnomalySeverity::HIGH, AnomalySeverity::fromString('high'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('critical'));
        
        // 中文
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('低'));
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalySeverity::fromString('中'));
        $this->assertEquals(AnomalySeverity::HIGH, AnomalySeverity::fromString('高'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('严重'));
        
        // 大写
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::fromString('LOW'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::fromString('CRITICAL'));
        
        // 无效值
        $this->assertNull(AnomalySeverity::fromString('invalid'));
        $this->assertNull(AnomalySeverity::fromString(''));
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = AnomalySeverity::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(AnomalySeverity::LOW, $cases);
        $this->assertContains(AnomalySeverity::MEDIUM, $cases);
        $this->assertContains(AnomalySeverity::HIGH, $cases);
        $this->assertContains(AnomalySeverity::CRITICAL, $cases);
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::from('low'));
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalySeverity::from('medium'));
        $this->assertEquals(AnomalySeverity::HIGH, AnomalySeverity::from('high'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::from('critical'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(AnomalySeverity::LOW, AnomalySeverity::tryFrom('low'));
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalySeverity::tryFrom('critical'));
        $this->assertNull(AnomalySeverity::tryFrom('invalid'));
    }
}