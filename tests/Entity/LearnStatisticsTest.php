<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

/**
 * @internal
 */
#[CoversClass(LearnStatistics::class)]
#[RunTestsInSeparateProcesses]
final class LearnStatisticsTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnStatistics();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'statisticsType' => ['statisticsType', StatisticsType::USER];
        yield 'statisticsPeriod' => ['statisticsPeriod', StatisticsPeriod::DAILY];
        yield 'statisticsDate' => ['statisticsDate', new \DateTimeImmutable('2024-01-01')];
        yield 'userStatistics' => ['userStatistics', ['newUsers' => 10, 'returnUsers' => 20]];
        yield 'courseStatistics' => ['courseStatistics', ['popular' => ['courseId' => 1, 'views' => 100]]];
        yield 'behaviorStatistics' => ['behaviorStatistics', ['play' => 100, 'pause' => 50]];
        yield 'anomalyStatistics' => ['anomalyStatistics', ['detection' => 5, 'resolution' => 3]];
        yield 'deviceStatistics' => ['deviceStatistics', ['mobile' => 60, 'desktop' => 40]];
        yield 'progressStatistics' => ['progressStatistics', ['completed' => 25, 'inProgress' => 75]];
        yield 'durationStatistics' => ['durationStatistics', ['avgSession' => 1800, 'total' => 3600]];
        yield 'totalUsers' => ['totalUsers', 100];
        yield 'activeUsers' => ['activeUsers', 50];
        yield 'totalSessions' => ['totalSessions', 200];
        yield 'totalDuration' => ['totalDuration', 3600.5];
        yield 'effectiveDuration' => ['effectiveDuration', 2400.75];
        yield 'anomalyCount' => ['anomalyCount', 5];
        yield 'completionRate' => ['completionRate', 85.5];
        yield 'averageEfficiency' => ['averageEfficiency', 0.85];
        yield 'extendedData' => ['extendedData', ['region' => 'beijing', 'peakHours' => [19, 20, 21]]];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
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
