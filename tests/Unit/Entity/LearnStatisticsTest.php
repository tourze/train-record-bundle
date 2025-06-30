<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

class LearnStatisticsTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new LearnStatistics();
        
        $this->assertInstanceOf(LearnStatistics::class, $entity);
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getStatisticsDate());
    }
    
    public function testStatisticsTypeProperty(): void
    {
        $entity = new LearnStatistics();
        $type = StatisticsType::USER;
        
        $entity->setStatisticsType($type);
        
        $this->assertEquals($type, $entity->getStatisticsType());
    }
    
    public function testStatisticsPeriodProperty(): void
    {
        $entity = new LearnStatistics();
        $period = StatisticsPeriod::DAILY;
        
        $entity->setStatisticsPeriod($period);
        
        $this->assertEquals($period, $entity->getStatisticsPeriod());
    }
    
    public function testStatisticsDateProperty(): void
    {
        $entity = new LearnStatistics();
        $date = new \DateTimeImmutable('2024-01-01');
        
        $entity->setStatisticsDate($date);
        
        $this->assertSame($date, $entity->getStatisticsDate());
    }
    
    public function testTotalUsersProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0, $entity->getTotalUsers());
        
        // 测试设置值
        $entity->setTotalUsers(100);
        $this->assertEquals(100, $entity->getTotalUsers());
        
        // 测试负数处理
        $entity->setTotalUsers(-10);
        $this->assertEquals(0, $entity->getTotalUsers());
    }
    
    public function testActiveUsersProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0, $entity->getActiveUsers());
        
        // 测试设置值
        $entity->setActiveUsers(50);
        $this->assertEquals(50, $entity->getActiveUsers());
    }
    
    public function testTotalSessionsProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0, $entity->getTotalSessions());
        
        // 测试设置值
        $entity->setTotalSessions(200);
        $this->assertEquals(200, $entity->getTotalSessions());
    }
    
    public function testTotalDurationProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0.0, $entity->getTotalDuration());
        
        // 测试设置值
        $entity->setTotalDuration(3600.5);
        $this->assertEquals(3600.5, $entity->getTotalDuration());
    }
    
    public function testEffectiveDurationProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0.0, $entity->getEffectiveDuration());
        
        // 测试设置值
        $entity->setEffectiveDuration(2400.75);
        $this->assertEquals(2400.75, $entity->getEffectiveDuration());
    }
    
    public function testAnomalyCountProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试默认值
        $this->assertEquals(0, $entity->getAnomalyCount());
        
        // 测试设置值
        $entity->setAnomalyCount(5);
        $this->assertEquals(5, $entity->getAnomalyCount());
    }
    
    public function testCompletionRateProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试设置正常值
        $entity->setCompletionRate(85.5);
        $this->assertEquals(85.5, $entity->getCompletionRate());
        
        // 测试边界值
        $entity->setCompletionRate(150.0);
        $this->assertEquals(100.0, $entity->getCompletionRate());
        
        $entity->setCompletionRate(-10.0);
        $this->assertEquals(0.0, $entity->getCompletionRate());
    }
    
    public function testAverageEfficiencyProperty(): void
    {
        $entity = new LearnStatistics();
        
        // 测试设置正常值
        $entity->setAverageEfficiency(0.85);
        $this->assertEquals(0.85, $entity->getAverageEfficiency());
        
        // 测试边界值
        $entity->setAverageEfficiency(1.5);
        $this->assertEquals(1.0, $entity->getAverageEfficiency());
        
        $entity->setAverageEfficiency(-0.5);
        $this->assertEquals(0.0, $entity->getAverageEfficiency());
    }
    
    public function testGetUserActiveRate(): void
    {
        $entity = new LearnStatistics();
        
        // 测试无用户的情况
        $this->assertEquals(0.0, $entity->getUserActiveRate());
        
        // 测试有用户的情况
        $entity->setTotalUsers(100);
        $entity->setActiveUsers(75);
        
        $this->assertEquals(0.75, $entity->getUserActiveRate());
    }
    
    public function testGetLearningEfficiency(): void
    {
        $entity = new LearnStatistics();
        
        // 测试无时长的情况
        $this->assertEquals(0.0, $entity->getLearningEfficiency());
        
        // 测试有时长的情况
        $entity->setTotalDuration(1000.0);
        $entity->setEffectiveDuration(800.0);
        
        $this->assertEquals(0.8, $entity->getLearningEfficiency());
    }
    
    public function testGetAnomalyRate(): void
    {
        $entity = new LearnStatistics();
        
        // 测试无会话的情况
        $this->assertEquals(0.0, $entity->getAnomalyRate());
        
        // 测试有会话的情况
        $entity->setTotalSessions(100);
        $entity->setAnomalyCount(5);
        
        $this->assertEquals(0.05, $entity->getAnomalyRate());
    }
    
    public function testGetFormattedDuration(): void
    {
        $entity = new LearnStatistics();
        
        // 测试小时格式
        $this->assertEquals('2小时30分钟', $entity->getFormattedDuration(9000));
        
        // 测试分钟格式
        $this->assertEquals('45分钟30秒', $entity->getFormattedDuration(2730));
        
        // 测试秒格式
        $this->assertEquals('45.0秒', $entity->getFormattedDuration(45));
    }
    
    public function testStatisticsDataProperties(): void
    {
        $entity = new LearnStatistics();
        
        // 测试用户统计
        $userStats = ['newUsers' => 10, 'returnUsers' => 20];
        $entity->setUserStatistics($userStats);
        $this->assertEquals($userStats, $entity->getUserStatistics());
        
        // 测试课程统计
        $courseStats = ['popular' => ['courseId' => 1, 'views' => 100]];
        $entity->setCourseStatistics($courseStats);
        $this->assertEquals($courseStats, $entity->getCourseStatistics());
        
        // 测试行为统计
        $behaviorStats = ['play' => 100, 'pause' => 50];
        $entity->setBehaviorStatistics($behaviorStats);
        $this->assertEquals($behaviorStats, $entity->getBehaviorStatistics());
    }
}