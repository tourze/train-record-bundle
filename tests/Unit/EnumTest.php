<?php

namespace Tourze\TrainRecordBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

/**
 * 枚举类单元测试
 */
class EnumTest extends TestCase
{
    public function test_behavior_type_enum(): void
    {
        // 测试基本值
        $this->assertEquals('play', BehaviorType::PLAY->value);
        $this->assertEquals('pause', BehaviorType::PAUSE->value);
        $this->assertEquals('mouse_click', BehaviorType::MOUSE_CLICK->value);
        
        // 测试标签
        $this->assertEquals('播放', BehaviorType::PLAY->getLabel());
        $this->assertEquals('暂停', BehaviorType::PAUSE->getLabel());
        $this->assertEquals('鼠标点击', BehaviorType::MOUSE_CLICK->getLabel());
        
        // 测试分类
        $this->assertEquals('video_control', BehaviorType::PLAY->getCategory());
        $this->assertEquals('video_control', BehaviorType::PAUSE->getCategory());
        $this->assertEquals('mouse_activity', BehaviorType::MOUSE_CLICK->getCategory());
        
        // 测试可疑行为
        $this->assertFalse(BehaviorType::PLAY->isSuspicious());
        $this->assertTrue(BehaviorType::RAPID_SEEK->isSuspicious());
        $this->assertTrue(BehaviorType::DEVELOPER_TOOLS->isSuspicious());
    }

    public function test_learn_action_enum(): void
    {
        // 测试基本值
        $this->assertEquals('start', LearnAction::START->value);
        $this->assertEquals('play', LearnAction::PLAY->value);
        $this->assertEquals('pause', LearnAction::PAUSE->value);
        $this->assertEquals('ended', LearnAction::ENDED->value);
        
        // 测试标签
        $this->assertEquals('开始学习', LearnAction::START->getLabel());
        $this->assertEquals('播放', LearnAction::PLAY->getLabel());
        $this->assertEquals('暂停', LearnAction::PAUSE->getLabel());
        $this->assertEquals('看完', LearnAction::ENDED->getLabel());
        
        // 测试其他动作
        $this->assertEquals('watch', LearnAction::WATCH->value);
        $this->assertEquals('观看', LearnAction::WATCH->getLabel());
        $this->assertEquals('practice', LearnAction::PRACTICE->value);
        $this->assertEquals('练习', LearnAction::PRACTICE->getLabel());
    }

    public function test_anomaly_type_enum(): void
    {
        // 测试基本值
        $this->assertEquals('multiple_device', AnomalyType::MULTIPLE_DEVICE->value);
        $this->assertEquals('rapid_progress', AnomalyType::RAPID_PROGRESS->value);
        $this->assertEquals('window_switch', AnomalyType::WINDOW_SWITCH->value);
        
        // 测试标签
        $this->assertEquals('多设备登录', AnomalyType::MULTIPLE_DEVICE->getLabel());
        $this->assertEquals('快速进度异常', AnomalyType::RAPID_PROGRESS->getLabel());
        $this->assertEquals('窗口切换异常', AnomalyType::WINDOW_SWITCH->getLabel());
        
        // 测试分类
        $this->assertEquals('device', AnomalyType::MULTIPLE_DEVICE->getCategory());
        $this->assertEquals('progress', AnomalyType::RAPID_PROGRESS->getCategory());
        $this->assertEquals('behavior', AnomalyType::WINDOW_SWITCH->getCategory());
        
        // 测试是否需要立即处理
        $this->assertTrue(AnomalyType::MULTIPLE_DEVICE->requiresImmediateAction());
        $this->assertFalse(AnomalyType::WINDOW_SWITCH->requiresImmediateAction());
    }

    public function test_anomaly_severity_enum(): void
    {
        // 测试基本值
        $this->assertEquals('low', AnomalySeverity::LOW->value);
        $this->assertEquals('medium', AnomalySeverity::MEDIUM->value);
        $this->assertEquals('high', AnomalySeverity::HIGH->value);
        $this->assertEquals('critical', AnomalySeverity::CRITICAL->value);
        
        // 测试标签
        $this->assertEquals('低', AnomalySeverity::LOW->getLabel());
        $this->assertEquals('中', AnomalySeverity::MEDIUM->getLabel());
        $this->assertEquals('高', AnomalySeverity::HIGH->getLabel());
        $this->assertEquals('严重', AnomalySeverity::CRITICAL->getLabel());
        
        // 测试权重
        $this->assertEquals(1, AnomalySeverity::LOW->getWeight());
        $this->assertEquals(2, AnomalySeverity::MEDIUM->getWeight());
        $this->assertEquals(3, AnomalySeverity::HIGH->getWeight());
        $this->assertEquals(4, AnomalySeverity::CRITICAL->getWeight());
        
        // 测试需要立即处理
        $this->assertFalse(AnomalySeverity::LOW->requiresImmediateAction());
        $this->assertFalse(AnomalySeverity::MEDIUM->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::HIGH->requiresImmediateAction());
        $this->assertTrue(AnomalySeverity::CRITICAL->requiresImmediateAction());
        
        // 测试颜色
        $this->assertEquals('green', AnomalySeverity::LOW->getColor());
        $this->assertEquals('red', AnomalySeverity::CRITICAL->getColor());
    }

    public function test_statistics_type_enum(): void
    {
        // 测试基本值
        $this->assertEquals('user', StatisticsType::USER->value);
        $this->assertEquals('course', StatisticsType::COURSE->value);
        $this->assertEquals('behavior', StatisticsType::BEHAVIOR->value);
        
        // 测试标签
        $this->assertEquals('用户统计', StatisticsType::USER->getLabel());
        $this->assertEquals('课程统计', StatisticsType::COURSE->getLabel());
        $this->assertEquals('行为统计', StatisticsType::BEHAVIOR->getLabel());
        
        // 测试分类
        $this->assertTrue(StatisticsType::USER->isCoreStatistics());
        $this->assertTrue(StatisticsType::COURSE->isCoreStatistics());
        $this->assertFalse(StatisticsType::BEHAVIOR->isCoreStatistics());
    }

    public function test_statistics_period_enum(): void
    {
        // 测试基本值
        $this->assertEquals('real_time', StatisticsPeriod::REAL_TIME->value);
        $this->assertEquals('hourly', StatisticsPeriod::HOURLY->value);
        $this->assertEquals('daily', StatisticsPeriod::DAILY->value);
        $this->assertEquals('weekly', StatisticsPeriod::WEEKLY->value);
        $this->assertEquals('monthly', StatisticsPeriod::MONTHLY->value);
        
        // 测试标签
        $this->assertEquals('实时', StatisticsPeriod::REAL_TIME->getLabel());
        $this->assertEquals('小时', StatisticsPeriod::HOURLY->getLabel());
        $this->assertEquals('日', StatisticsPeriod::DAILY->getLabel());
        $this->assertEquals('周', StatisticsPeriod::WEEKLY->getLabel());
        $this->assertEquals('月', StatisticsPeriod::MONTHLY->getLabel());
        
        // 测试MySQL日期格式
        $this->assertEquals('%Y-%m-%d %H:%i:%s', StatisticsPeriod::REAL_TIME->getMySQLDateFormat());
        $this->assertEquals('%Y-%m-%d %H:00:00', StatisticsPeriod::HOURLY->getMySQLDateFormat());
        $this->assertEquals('%Y-%m-%d', StatisticsPeriod::DAILY->getMySQLDateFormat());
        $this->assertEquals('%Y-%u', StatisticsPeriod::WEEKLY->getMySQLDateFormat());
        $this->assertEquals('%Y-%m', StatisticsPeriod::MONTHLY->getMySQLDateFormat());
        
        // 测试秒数
        $this->assertEquals(0, StatisticsPeriod::REAL_TIME->getSeconds());
        $this->assertEquals(3600, StatisticsPeriod::HOURLY->getSeconds());
        $this->assertEquals(86400, StatisticsPeriod::DAILY->getSeconds());
        $this->assertEquals(604800, StatisticsPeriod::WEEKLY->getSeconds());
        $this->assertEquals(2592000, StatisticsPeriod::MONTHLY->getSeconds());
    }

    public function test_enum_from_method(): void
    {
        // 测试从字符串创建枚举
        $behaviorType = BehaviorType::from('mouse_click');
        $this->assertEquals(BehaviorType::MOUSE_CLICK, $behaviorType);
        
        $learnAction = LearnAction::from('start');
        $this->assertEquals(LearnAction::START, $learnAction);
        
        $anomalyType = AnomalyType::from('multiple_device');
        $this->assertEquals(AnomalyType::MULTIPLE_DEVICE, $anomalyType);
        
        $severity = AnomalySeverity::from('high');
        $this->assertEquals(AnomalySeverity::HIGH, $severity);
        
        $statsType = StatisticsType::from('user');
        $this->assertEquals(StatisticsType::USER, $statsType);
        
        $period = StatisticsPeriod::from('daily');
        $this->assertEquals(StatisticsPeriod::DAILY, $period);
    }

    public function test_enum_cases(): void
    {
        // 测试获取所有枚举值
        $behaviorTypes = BehaviorType::cases();
        $this->assertGreaterThan(0, count($behaviorTypes));
        $this->assertContains(BehaviorType::MOUSE_CLICK, $behaviorTypes);
        
        $learnActions = LearnAction::cases();
        $this->assertGreaterThan(0, count($learnActions));
        $this->assertContains(LearnAction::START, $learnActions);
        
        $anomalyTypes = AnomalyType::cases();
        $this->assertGreaterThan(0, count($anomalyTypes));
        $this->assertContains(AnomalyType::MULTIPLE_DEVICE, $anomalyTypes);
        
        $severities = AnomalySeverity::cases();
        $this->assertEquals(4, count($severities));
        $this->assertContains(AnomalySeverity::CRITICAL, $severities);
        
        $statsTypes = StatisticsType::cases();
        $this->assertGreaterThan(0, count($statsTypes));
        $this->assertContains(StatisticsType::USER, $statsTypes);
        
        $periods = StatisticsPeriod::cases();
        $this->assertGreaterThan(0, count($periods));
        $this->assertContains(StatisticsPeriod::DAILY, $periods);
    }
} 