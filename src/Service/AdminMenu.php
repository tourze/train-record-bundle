<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\TrainRecordBundle\Controller\Admin\EffectiveStudyRecordCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\FaceDetectCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnAnomalyCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnArchiveCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnBehaviorCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnDeviceCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnLogCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnProgressCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnSessionCrudController;
use Tourze\TrainRecordBundle\Controller\Admin\LearnStatisticsCrudController;

/**
 * 训练记录包EasyAdmin菜单配置服务
 */
class AdminMenu implements MenuProviderInterface
{
    /**
     * 实现MenuProviderInterface接口方法
     */
    public function __invoke(ItemInterface $item): void
    {
        // 该方法被自动调用以注册菜单项
        // 实际菜单项通过getTrainRecordMenuItems()等方法获取
    }

    /**
     * 获取训练记录管理菜单项
     *
     * @return MenuItemInterface[]
     */
    public function getTrainRecordMenuItems(): array
    {
        return [
            // 主菜单分组
            MenuItem::section('训练记录管理', 'fas fa-graduation-cap')->setPermission('ROLE_ADMIN'),

            // 学习会话管理
            MenuItem::linkToCrud('学习会话', 'fas fa-play-circle', LearnSessionCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 学习进度管理
            MenuItem::linkToCrud('学习进度', 'fas fa-chart-line', LearnProgressCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 有效学时记录
            MenuItem::linkToCrud('有效学时记录', 'fas fa-clock', EffectiveStudyRecordCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 学习日志
            MenuItem::linkToCrud('学习日志', 'fas fa-file-alt', LearnLogCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 学习行为分析
            MenuItem::section('行为分析', 'fas fa-brain')->setPermission('ROLE_ADMIN'),

            // 学习行为记录
            MenuItem::linkToCrud('学习行为', 'fas fa-user-check', LearnBehaviorCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 人脸检测
            MenuItem::linkToCrud('人脸检测', 'fas fa-camera', FaceDetectCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 异常检测
            MenuItem::linkToCrud('学习异常', 'fas fa-exclamation-triangle', LearnAnomalyCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 设备与统计
            MenuItem::section('设备与统计', 'fas fa-chart-bar')->setPermission('ROLE_ADMIN'),

            // 学习设备
            MenuItem::linkToCrud('学习设备', 'fas fa-desktop', LearnDeviceCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            // 学习统计
            MenuItem::linkToCrud('学习统计', 'fas fa-analytics', LearnStatisticsCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),

            // 归档管理
            MenuItem::linkToCrud('归档管理', 'fas fa-archive', LearnArchiveCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),
        ];
    }

    /**
     * 获取简化的菜单项（仅核心功能）
     *
     * @return MenuItemInterface[]
     */
    public function getCoreMenuItems(): array
    {
        return [
            MenuItem::section('训练记录', 'fas fa-graduation-cap')->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('学习会话', 'fas fa-play-circle', LearnSessionCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('学习进度', 'fas fa-chart-line', LearnProgressCrudController::class)
                ->setPermission('ROLE_ADMIN'),
            MenuItem::linkToCrud('有效学时', 'fas fa-clock', EffectiveStudyRecordCrudController::class)
                ->setPermission('ROLE_ADMIN'),
        ];
    }

    /**
     * 获取管理员级别菜单项
     *
     * @return MenuItemInterface[]
     */
    public function getAdminMenuItems(): array
    {
        return [
            MenuItem::section('高级管理', 'fas fa-cogs')->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('学习统计', 'fas fa-analytics', LearnStatisticsCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('异常检测', 'fas fa-exclamation-triangle', LearnAnomalyCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),
            MenuItem::linkToCrud('归档管理', 'fas fa-archive', LearnArchiveCrudController::class)
                ->setPermission('ROLE_SUPER_ADMIN'),
        ];
    }

    /**
     * 获取所有菜单项
     *
     * @return MenuItemInterface[]
     */
    public function getAllMenuItems(): array
    {
        return array_merge(
            $this->getTrainRecordMenuItems(),
            $this->getAdminMenuItems()
        );
    }
}
