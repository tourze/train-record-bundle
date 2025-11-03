<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineAsyncInsertBundle\DoctrineAsyncInsertBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineResolveTargetEntityBundle\DoctrineResolveTargetEntityBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineUserAgentBundle\DoctrineUserAgentBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\JsonRPCLockBundle\JsonRPCLockBundle;
use Tourze\JsonRPCLogBundle\JsonRPCLogBundle;
use Tourze\JsonRPCSecurityBundle\JsonRPCSecurityBundle;
use Tourze\TrainClassroomBundle\TrainClassroomBundle;
use Tourze\TrainCourseBundle\TrainCourseBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;

class TrainRecordBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineAsyncInsertBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineResolveTargetEntityBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineUserAgentBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            JsonRPCLogBundle::class => ['all' => true],
            JsonRPCLockBundle::class => ['all' => true],
            JsonRPCSecurityBundle::class => ['all' => true],
            TrainCourseBundle::class => ['all' => true],
            TrainClassroomBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
    }
}
