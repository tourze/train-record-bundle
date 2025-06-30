<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;

/**
 * StatisticsPeriod 枚举测试
 */
class StatisticsPeriodTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('daily', StatisticsPeriod::DAILY->value);
        $this->assertEquals('weekly', StatisticsPeriod::WEEKLY->value);
        $this->assertEquals('monthly', StatisticsPeriod::MONTHLY->value);
        $this->assertEquals('quarterly', StatisticsPeriod::QUARTERLY->value);
        $this->assertEquals('yearly', StatisticsPeriod::YEARLY->value);
        $this->assertEquals('hourly', StatisticsPeriod::HOURLY->value);
        $this->assertEquals('real_time', StatisticsPeriod::REAL_TIME->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('小时', StatisticsPeriod::HOURLY->getLabel());
        $this->assertEquals('日', StatisticsPeriod::DAILY->getLabel());
        $this->assertEquals('周', StatisticsPeriod::WEEKLY->getLabel());
        $this->assertEquals('月', StatisticsPeriod::MONTHLY->getLabel());
        $this->assertEquals('季度', StatisticsPeriod::QUARTERLY->getLabel());
        $this->assertEquals('年', StatisticsPeriod::YEARLY->getLabel());
        $this->assertEquals('实时', StatisticsPeriod::REAL_TIME->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('按小时统计数据', StatisticsPeriod::HOURLY->getDescription());
        $this->assertEquals('按天统计数据', StatisticsPeriod::DAILY->getDescription());
        $this->assertEquals('按周统计数据', StatisticsPeriod::WEEKLY->getDescription());
        $this->assertEquals('按月统计数据', StatisticsPeriod::MONTHLY->getDescription());
        $this->assertEquals('按季度统计数据', StatisticsPeriod::QUARTERLY->getDescription());
        $this->assertEquals('按年统计数据', StatisticsPeriod::YEARLY->getDescription());
        $this->assertEquals('实时统计数据', StatisticsPeriod::REAL_TIME->getDescription());
    }

    /**
     * 测试获取周期的秒数
     */
    public function test_get_seconds(): void
    {
        $this->assertEquals(3600, StatisticsPeriod::HOURLY->getSeconds());
        $this->assertEquals(86400, StatisticsPeriod::DAILY->getSeconds());
        $this->assertEquals(604800, StatisticsPeriod::WEEKLY->getSeconds());
        $this->assertEquals(2592000, StatisticsPeriod::MONTHLY->getSeconds());
        $this->assertEquals(7776000, StatisticsPeriod::QUARTERLY->getSeconds());
        $this->assertEquals(31536000, StatisticsPeriod::YEARLY->getSeconds());
        $this->assertEquals(0, StatisticsPeriod::REAL_TIME->getSeconds());
    }

    /**
     * 测试获取 DateInterval 格式
     */
    public function test_get_date_interval(): void
    {
        $this->assertEquals('PT1H', StatisticsPeriod::HOURLY->getDateInterval());
        $this->assertEquals('P1D', StatisticsPeriod::DAILY->getDateInterval());
        $this->assertEquals('P1W', StatisticsPeriod::WEEKLY->getDateInterval());
        $this->assertEquals('P1M', StatisticsPeriod::MONTHLY->getDateInterval());
        $this->assertEquals('P3M', StatisticsPeriod::QUARTERLY->getDateInterval());
        $this->assertEquals('P1Y', StatisticsPeriod::YEARLY->getDateInterval());
        $this->assertEquals('PT0S', StatisticsPeriod::REAL_TIME->getDateInterval());
    }

    /**
     * 测试获取 MySQL 日期格式
     */
    public function test_get_mysql_date_format(): void
    {
        $this->assertEquals('%Y-%m-%d %H:00:00', StatisticsPeriod::HOURLY->getMySQLDateFormat());
        $this->assertEquals('%Y-%m-%d', StatisticsPeriod::DAILY->getMySQLDateFormat());
        $this->assertEquals('%Y-%u', StatisticsPeriod::WEEKLY->getMySQLDateFormat());
        $this->assertEquals('%Y-%m', StatisticsPeriod::MONTHLY->getMySQLDateFormat());
        $this->assertEquals('%Y-Q%q', StatisticsPeriod::QUARTERLY->getMySQLDateFormat());
        $this->assertEquals('%Y', StatisticsPeriod::YEARLY->getMySQLDateFormat());
        $this->assertEquals('%Y-%m-%d %H:%i:%s', StatisticsPeriod::REAL_TIME->getMySQLDateFormat());
    }

    /**
     * 测试获取 PHP 日期格式
     */
    public function test_get_php_date_format(): void
    {
        $this->assertEquals('Y-m-d H:00:00', StatisticsPeriod::HOURLY->getPHPDateFormat());
        $this->assertEquals('Y-m-d', StatisticsPeriod::DAILY->getPHPDateFormat());
        $this->assertEquals('Y-W', StatisticsPeriod::WEEKLY->getPHPDateFormat());
        $this->assertEquals('Y-m', StatisticsPeriod::MONTHLY->getPHPDateFormat());
        $this->assertEquals('Y-\QQ', StatisticsPeriod::QUARTERLY->getPHPDateFormat());
        $this->assertEquals('Y', StatisticsPeriod::YEARLY->getPHPDateFormat());
        $this->assertEquals('Y-m-d H:i:s', StatisticsPeriod::REAL_TIME->getPHPDateFormat());
    }

    /**
     * 测试获取统计频率
     */
    public function test_get_frequency_per_day(): void
    {
        $this->assertEquals(24, StatisticsPeriod::HOURLY->getFrequencyPerDay());
        $this->assertEquals(1, StatisticsPeriod::DAILY->getFrequencyPerDay());
        $this->assertEquals(1, StatisticsPeriod::WEEKLY->getFrequencyPerDay());
        $this->assertEquals(1, StatisticsPeriod::MONTHLY->getFrequencyPerDay());
        $this->assertEquals(1, StatisticsPeriod::QUARTERLY->getFrequencyPerDay());
        $this->assertEquals(1, StatisticsPeriod::YEARLY->getFrequencyPerDay());
        $this->assertEquals(1440, StatisticsPeriod::REAL_TIME->getFrequencyPerDay());
    }

    /**
     * 测试是否为高频统计
     */
    public function test_is_high_frequency(): void
    {
        $this->assertTrue(StatisticsPeriod::REAL_TIME->isHighFrequency());
        $this->assertTrue(StatisticsPeriod::HOURLY->isHighFrequency());
        $this->assertFalse(StatisticsPeriod::DAILY->isHighFrequency());
        $this->assertFalse(StatisticsPeriod::WEEKLY->isHighFrequency());
        $this->assertFalse(StatisticsPeriod::MONTHLY->isHighFrequency());
        $this->assertFalse(StatisticsPeriod::QUARTERLY->isHighFrequency());
        $this->assertFalse(StatisticsPeriod::YEARLY->isHighFrequency());
    }

    /**
     * 测试是否为低频统计
     */
    public function test_is_low_frequency(): void
    {
        $this->assertFalse(StatisticsPeriod::REAL_TIME->isLowFrequency());
        $this->assertFalse(StatisticsPeriod::HOURLY->isLowFrequency());
        $this->assertFalse(StatisticsPeriod::DAILY->isLowFrequency());
        $this->assertFalse(StatisticsPeriod::WEEKLY->isLowFrequency());
        $this->assertFalse(StatisticsPeriod::MONTHLY->isLowFrequency());
        $this->assertTrue(StatisticsPeriod::QUARTERLY->isLowFrequency());
        $this->assertTrue(StatisticsPeriod::YEARLY->isLowFrequency());
    }

    /**
     * 测试获取下一个统计时间
     */
    public function test_get_next_statistics_time(): void
    {
        $currentTime = new \DateTime('2024-01-15 10:30:00');
        
        // 测试小时统计
        $nextHourly = StatisticsPeriod::HOURLY->getNextStatisticsTime($currentTime);
        $this->assertEquals('2024-01-15 11:30:00', $nextHourly->format('Y-m-d H:i:s'));
        
        // 测试日统计
        $nextDaily = StatisticsPeriod::DAILY->getNextStatisticsTime($currentTime);
        $this->assertEquals('2024-01-16 10:30:00', $nextDaily->format('Y-m-d H:i:s'));
        
        // 测试周统计
        $nextWeekly = StatisticsPeriod::WEEKLY->getNextStatisticsTime($currentTime);
        $this->assertEquals('2024-01-22 10:30:00', $nextWeekly->format('Y-m-d H:i:s'));
        
        // 测试月统计
        $nextMonthly = StatisticsPeriod::MONTHLY->getNextStatisticsTime($currentTime);
        $this->assertEquals('2024-02-15 10:30:00', $nextMonthly->format('Y-m-d H:i:s'));
        
        // 测试实时统计（每分钟）
        $nextRealTime = StatisticsPeriod::REAL_TIME->getNextStatisticsTime($currentTime);
        $this->assertEquals('2024-01-15 10:31:00', $nextRealTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试格式化统计时间
     */
    public function test_format_statistics_time(): void
    {
        $time = new \DateTime('2024-01-15 10:30:45');
        
        $this->assertEquals('2024-01-15 10:00:00', StatisticsPeriod::HOURLY->formatStatisticsTime($time));
        $this->assertEquals('2024-01-15', StatisticsPeriod::DAILY->formatStatisticsTime($time));
        $this->assertEquals('2024-03', StatisticsPeriod::WEEKLY->formatStatisticsTime($time));
        $this->assertEquals('2024-01', StatisticsPeriod::MONTHLY->formatStatisticsTime($time));
        $this->assertEquals('2024-QQ', StatisticsPeriod::QUARTERLY->formatStatisticsTime($time));
        $this->assertEquals('2024', StatisticsPeriod::YEARLY->formatStatisticsTime($time));
        $this->assertEquals('2024-01-15 10:30:45', StatisticsPeriod::REAL_TIME->formatStatisticsTime($time));
    }

    /**
     * 测试获取所有周期
     */
    public function test_get_all_periods(): void
    {
        $periods = StatisticsPeriod::getAllPeriods();
        
        $this->assertCount(7, $periods);
        $this->assertContains(StatisticsPeriod::REAL_TIME, $periods);
        $this->assertContains(StatisticsPeriod::HOURLY, $periods);
        $this->assertContains(StatisticsPeriod::DAILY, $periods);
        $this->assertContains(StatisticsPeriod::WEEKLY, $periods);
        $this->assertContains(StatisticsPeriod::MONTHLY, $periods);
        $this->assertContains(StatisticsPeriod::QUARTERLY, $periods);
        $this->assertContains(StatisticsPeriod::YEARLY, $periods);
    }

    /**
     * 测试获取常用周期
     */
    public function test_get_common_periods(): void
    {
        $periods = StatisticsPeriod::getCommonPeriods();
        
        $this->assertCount(4, $periods);
        $this->assertContains(StatisticsPeriod::DAILY, $periods);
        $this->assertContains(StatisticsPeriod::WEEKLY, $periods);
        $this->assertContains(StatisticsPeriod::MONTHLY, $periods);
        $this->assertContains(StatisticsPeriod::YEARLY, $periods);
        
        // 不包含实时和小时统计
        $this->assertNotContains(StatisticsPeriod::REAL_TIME, $periods);
        $this->assertNotContains(StatisticsPeriod::HOURLY, $periods);
        $this->assertNotContains(StatisticsPeriod::QUARTERLY, $periods);
    }

    /**
     * 测试按频率排序
     */
    public function test_get_sorted_by_frequency(): void
    {
        $sorted = StatisticsPeriod::getSortedByFrequency();
        
        $this->assertCount(7, $sorted);
        
        // 验证实时统计应该排在第一位（最高频率）
        $this->assertEquals(StatisticsPeriod::REAL_TIME, $sorted[0]);
        
        // 验证排序顺序
        $previousFrequency = PHP_INT_MAX;
        foreach ($sorted as $period) {
            $currentFrequency = $period->getFrequencyPerDay();
            $this->assertLessThanOrEqual($previousFrequency, $currentFrequency);
            $previousFrequency = $currentFrequency;
        }
    }

    /**
     * 测试从字符串创建
     */
    public function test_from_string(): void
    {
        // 英文
        $this->assertEquals(StatisticsPeriod::HOURLY, StatisticsPeriod::fromString('hourly'));
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::fromString('daily'));
        $this->assertEquals(StatisticsPeriod::WEEKLY, StatisticsPeriod::fromString('weekly'));
        $this->assertEquals(StatisticsPeriod::MONTHLY, StatisticsPeriod::fromString('monthly'));
        $this->assertEquals(StatisticsPeriod::QUARTERLY, StatisticsPeriod::fromString('quarterly'));
        $this->assertEquals(StatisticsPeriod::YEARLY, StatisticsPeriod::fromString('yearly'));
        $this->assertEquals(StatisticsPeriod::REAL_TIME, StatisticsPeriod::fromString('real_time'));
        
        // 中文
        $this->assertEquals(StatisticsPeriod::HOURLY, StatisticsPeriod::fromString('小时'));
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::fromString('日'));
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::fromString('天'));
        $this->assertEquals(StatisticsPeriod::WEEKLY, StatisticsPeriod::fromString('周'));
        $this->assertEquals(StatisticsPeriod::MONTHLY, StatisticsPeriod::fromString('月'));
        $this->assertEquals(StatisticsPeriod::QUARTERLY, StatisticsPeriod::fromString('季度'));
        $this->assertEquals(StatisticsPeriod::YEARLY, StatisticsPeriod::fromString('年'));
        $this->assertEquals(StatisticsPeriod::REAL_TIME, StatisticsPeriod::fromString('实时'));
        
        // 大写
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::fromString('DAILY'));
        
        // 无效值
        $this->assertNull(StatisticsPeriod::fromString('invalid'));
        $this->assertNull(StatisticsPeriod::fromString(''));
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = StatisticsPeriod::cases();
        
        $this->assertCount(7, $cases);
        foreach ($cases as $case) {
            $this->assertInstanceOf(StatisticsPeriod::class, $case);
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::from('daily'));
        $this->assertEquals(StatisticsPeriod::MONTHLY, StatisticsPeriod::from('monthly'));
        $this->assertEquals(StatisticsPeriod::REAL_TIME, StatisticsPeriod::from('real_time'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(StatisticsPeriod::DAILY, StatisticsPeriod::tryFrom('daily'));
        $this->assertEquals(StatisticsPeriod::YEARLY, StatisticsPeriod::tryFrom('yearly'));
        $this->assertNull(StatisticsPeriod::tryFrom('invalid_period'));
    }
}