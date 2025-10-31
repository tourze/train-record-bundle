<?php

namespace Tourze\TrainRecordBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Service\LearnAnalyticsService;
use Tourze\TrainRecordBundle\Service\LearnArchiveService;
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Service\LearnDeviceService;
use Tourze\TrainRecordBundle\Service\LearnProgressService;
use Tourze\TrainRecordBundle\Service\LearnSessionService;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * 集成测试类
 * 测试Bundle的服务注册和枚举功能
 *
 * @internal
 */
#[CoversClass(TrainRecordBundle::class)]
#[RunTestsInSeparateProcesses]
final class TrainRecordIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * 测试服务是否正确注册
     */
    protected function onSetUp(): void
    {
        // 在这里初始化测试需要的属性
    }

    public function testServicesAreRegistered(): void
    {
        $container = self::getContainer();

        // 测试所有核心服务是否注册
        $services = [
            LearnSessionService::class,
            LearnBehaviorService::class,
            LearnDeviceService::class,
            LearnProgressService::class,
            LearnArchiveService::class,
            LearnAnalyticsService::class,
        ];

        foreach ($services as $service) {
            if (!$container->has($service)) {
                self::fail("Service {$service} is not registered in the container");
            }
        }

        // 测试服务实例化
        $this->assertInstanceOf(LearnSessionService::class, $container->get(LearnSessionService::class));
        $this->assertInstanceOf(LearnBehaviorService::class, $container->get(LearnBehaviorService::class));
        $this->assertInstanceOf(LearnDeviceService::class, $container->get(LearnDeviceService::class));
        $this->assertInstanceOf(LearnProgressService::class, $container->get(LearnProgressService::class));
        $this->assertInstanceOf(LearnArchiveService::class, $container->get(LearnArchiveService::class));
        $this->assertInstanceOf(LearnAnalyticsService::class, $container->get(LearnAnalyticsService::class));
    }

    /**
     * 测试枚举类功能
     */
    public function testEnumsFunctionality(): void
    {
        // 测试BehaviorType枚举
        $playBehavior = BehaviorType::PLAY;
        $this->assertEquals('play', $playBehavior->value);
        $this->assertEquals('播放', $playBehavior->getLabel());
        $this->assertEquals('video_control', $playBehavior->getCategory());
        $this->assertFalse($playBehavior->isSuspicious());

        $rapidSeekBehavior = BehaviorType::RAPID_SEEK;
        $this->assertTrue($rapidSeekBehavior->isSuspicious());

        // 测试AnomalyType枚举
        $multipleDeviceAnomaly = AnomalyType::MULTIPLE_DEVICE;
        $this->assertEquals('multiple_device', $multipleDeviceAnomaly->value);
        $this->assertEquals('多设备登录', $multipleDeviceAnomaly->getLabel());

        // 测试AnomalySeverity枚举
        $highSeverity = AnomalySeverity::HIGH;
        $this->assertEquals('high', $highSeverity->value);
        $this->assertEquals('高', $highSeverity->getLabel());

        // 测试ArchiveFormat枚举
        $jsonFormat = ArchiveFormat::JSON;
        $this->assertEquals('json', $jsonFormat->value);
        $this->assertEquals('JSON', $jsonFormat->getLabel());

        // 测试StatisticsType枚举
        $userStats = StatisticsType::USER;
        $this->assertEquals('user', $userStats->value);
        $this->assertEquals('用户统计', $userStats->getLabel());
        $this->assertTrue($userStats->isCoreStatistics());

        // 测试StatisticsPeriod枚举
        $dailyPeriod = StatisticsPeriod::DAILY;
        $this->assertEquals('daily', $dailyPeriod->value);
        $this->assertEquals('日', $dailyPeriod->getLabel());
        $this->assertEquals('%Y-%m-%d', $dailyPeriod->getMySQLDateFormat());
    }
}
