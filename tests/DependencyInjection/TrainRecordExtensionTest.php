<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TrainRecordBundle\Command\LearnArchiveCommand;
use Tourze\TrainRecordBundle\Command\Operation\ArchiveOperationFactory;
use Tourze\TrainRecordBundle\DependencyInjection\TrainRecordExtension;
use Tourze\TrainRecordBundle\Service\LearnArchiveService;
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Service\LearnDeviceService;
use Tourze\TrainRecordBundle\Service\LearnProgressService;
use Tourze\TrainRecordBundle\Service\LearnSessionService;

/**
 * TrainRecordExtension 测试
 *
 * @internal
 */
#[CoversClass(TrainRecordExtension::class)]
#[RunTestsInSeparateProcesses]
final class TrainRecordExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private TrainRecordExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new TrainRecordExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 添加 logger 服务定义 (Extension 依赖的基础服务)
        $container->register('logger', LoggerInterface::class);
        $container->register('doctrine.orm.entity_manager', EntityManagerInterface::class);

        $this->extension->load([], $container);

        // 验证核心服务是否被正确注册
        $this->assertTrue($container->hasDefinition(LearnSessionService::class));
        $this->assertTrue($container->hasDefinition(LearnBehaviorService::class));
        $this->assertTrue($container->hasDefinition(LearnDeviceService::class));
        $this->assertTrue($container->hasDefinition(LearnProgressService::class));
        $this->assertTrue($container->hasDefinition(LearnArchiveService::class));

        // 验证命令是否被注册
        $this->assertTrue($container->hasDefinition(LearnArchiveCommand::class));

        // 验证操作类是否被注册
        $this->assertTrue($container->hasDefinition(ArchiveOperationFactory::class));

        // 验证服务配置
        $learnArchiveCommandDef = $container->getDefinition(LearnArchiveCommand::class);
        $this->assertTrue($learnArchiveCommandDef->hasTag('console.command'));
        $this->assertCount(2, $learnArchiveCommandDef->getArguments());
    }
}
