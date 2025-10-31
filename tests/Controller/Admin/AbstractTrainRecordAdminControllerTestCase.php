<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Controller\Admin;

use App\Controller\Admin\DashboardController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * Train Record Bundle 管理后台测试基类
 *
 * 统一处理 EasyAdmin Dashboard 配置，解决测试环境中 URL 生成的问题
 */
#[CoversClass(AbstractEasyAdminControllerTestCase::class)]
#[RunTestsInSeparateProcesses]
abstract class AbstractTrainRecordAdminControllerTestCase extends AbstractEasyAdminControllerTestCase
{
    /**
     * 指定使用的主应用 Dashboard
     */
    protected function getPreferredDashboardControllerFqcn(): ?string
    {
        return DashboardController::class;
    }
}