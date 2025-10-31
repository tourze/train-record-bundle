<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Service;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\TrainRecordBundle\Service\AdminMenu;

/**
 * AdminMenu服务测试
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testGetTrainRecordMenuItems(): void
    {
        $menuItems = $this->adminMenu->getTrainRecordMenuItems();

        $this->assertIsArray($menuItems);
        $this->assertNotEmpty($menuItems);

        // 检查菜单项数量是否符合预期
        $this->assertGreaterThan(10, count($menuItems), '应该有足够数量的菜单项');

        // 检查是否都是MenuItemInterface实例
        foreach ($menuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item, '每个菜单项都应该是MenuItemInterface实例');
        }
    }

    public function testGetCoreMenuItems(): void
    {
        $menuItems = $this->adminMenu->getCoreMenuItems();

        $this->assertIsArray($menuItems);
        $this->assertNotEmpty($menuItems);
        $this->assertCount(4, $menuItems); // 1个分组 + 3个核心菜单项

        // 检查是否都是MenuItemInterface实例
        foreach ($menuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testGetAdminMenuItems(): void
    {
        $menuItems = $this->adminMenu->getAdminMenuItems();

        $this->assertIsArray($menuItems);
        $this->assertNotEmpty($menuItems);

        // 检查是否都是MenuItemInterface实例
        foreach ($menuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testGetAllMenuItems(): void
    {
        $allMenuItems = $this->adminMenu->getAllMenuItems();
        $trainRecordItems = $this->adminMenu->getTrainRecordMenuItems();
        $adminItems = $this->adminMenu->getAdminMenuItems();

        $this->assertIsArray($allMenuItems);
        $this->assertNotEmpty($allMenuItems);

        // 验证总数等于两个部分的总和
        $expectedCount = count($trainRecordItems) + count($adminItems);
        $this->assertCount($expectedCount, $allMenuItems);
    }

    public function testMenuItemsAreValidInstances(): void
    {
        $menuItems = $this->adminMenu->getTrainRecordMenuItems();

        foreach ($menuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item, '菜单项应该是MenuItemInterface实例');
        }
    }

    public function testAllMethodsReturnArrays(): void
    {
        $this->assertIsArray($this->adminMenu->getTrainRecordMenuItems());
        $this->assertIsArray($this->adminMenu->getCoreMenuItems());
        $this->assertIsArray($this->adminMenu->getAdminMenuItems());
        $this->assertIsArray($this->adminMenu->getAllMenuItems());
    }

    public function testMenuMethodsReturnNonEmptyArrays(): void
    {
        $this->assertNotEmpty($this->adminMenu->getTrainRecordMenuItems());
        $this->assertNotEmpty($this->adminMenu->getCoreMenuItems());
        $this->assertNotEmpty($this->adminMenu->getAdminMenuItems());
        $this->assertNotEmpty($this->adminMenu->getAllMenuItems());
    }

    public function testTrainRecordMenuHasMostItems(): void
    {
        $trainRecordItems = $this->adminMenu->getTrainRecordMenuItems();
        $coreItems = $this->adminMenu->getCoreMenuItems();
        $adminItems = $this->adminMenu->getAdminMenuItems();

        // 训练记录菜单应该有最多的项目
        $this->assertGreaterThan(count($coreItems), count($trainRecordItems));
        $this->assertGreaterThan(count($adminItems), count($trainRecordItems));
    }

    public function testInvokeMethod(): void
    {
        // 测试__invoke方法的基本功能
        // 该方法目前为空实现，只是作为接口契约存在
        $this->expectNotToPerformAssertions();

        // 创建Mock而不验证交互，因为__invoke方法为空实现
        $item = $this->createMock(ItemInterface::class);
        $this->adminMenu->__invoke($item);
    }
}
