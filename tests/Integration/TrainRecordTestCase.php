<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\AliyunVodBundle\AliyunVodBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserAgentBundle\DoctrineUserAgentBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\JsonRPCLogBundle\JsonRPCLogBundle;
use Tourze\TrainCategoryBundle\TrainCategoryBundle;
use Tourze\TrainClassroomBundle\TrainClassroomBundle;
use Tourze\TrainCourseBundle\TrainCourseBundle;
use Tourze\TrainRecordBundle\TrainRecordBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

abstract class TrainRecordTestCase extends KernelTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Symfony bundles
            SecurityBundle::class => ['all' => true],
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineUserAgentBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Supporting bundles
            JsonRPCLogBundle::class => ['all' => true],
            AliyunVodBundle::class => ['all' => true],
            TrainCategoryBundle::class => ['all' => true],
            TrainCourseBundle::class => ['all' => true],
            TrainClassroomBundle::class => ['all' => true],
            // Core bundles
            TrainRecordBundle::class => ['all' => true],
        ]);
    }
}