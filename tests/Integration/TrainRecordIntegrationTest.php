<?php

namespace Tourze\TrainRecordBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
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
 */
class TrainRecordIntegrationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            TrainRecordBundle::class => ['all' => true],
        ]);
    }

    /**
     * 测试服务是否正确注册
     */
    public function test_services_are_registered(): void
    {
        $container = static::getContainer();

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
                $this->fail("Service {$service} is not registered in the container");
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
    public function test_enums_functionality(): void
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

    /**
     * 测试Bundle配置加载
     */
    public function test_bundle_configuration(): void
    {
        $container = static::getContainer();
        
        // 测试Bundle是否正确加载
        $this->assertTrue($container->hasParameter('kernel.bundles'));
        $bundles = $container->getParameter('kernel.bundles');
        $this->assertArrayHasKey('TrainRecordBundle', $bundles);
        $this->assertEquals('Tourze\\TrainRecordBundle\\TrainRecordBundle', $bundles['TrainRecordBundle']);
    }
} 