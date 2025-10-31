<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\TrainRecordBundle\Tests\Controller\Admin\AbstractTrainRecordAdminControllerTestCase;
use Tourze\TrainRecordBundle\Controller\Admin\LearnLogCrudController;
use Tourze\TrainRecordBundle\Entity\LearnLog;

/**
 * @internal
 */
#[CoversClass(LearnLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class LearnLogCrudControllerTest extends AbstractTrainRecordAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<LearnLog>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(LearnLogCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'learnSession' => ['学习会话'];
        yield 'action' => ['操作类型'];
        yield 'createTime' => ['操作时间'];
        yield 'createdFromIp' => ['IP地址'];
        yield 'createdFromUa' => ['用户代理'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'learnSession' => ['learnSession'];
        yield 'action' => ['action'];
        yield 'createTime' => ['createTime'];
        yield 'createdFromIp' => ['createdFromIp'];
        yield 'createdFromUa' => ['createdFromUa'];
        yield 'message' => ['message'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'learnSession' => ['learnSession'];
        yield 'action' => ['action'];
        yield 'createTime' => ['createTime'];
        yield 'createdFromIp' => ['createdFromIp'];
        yield 'createdFromUa' => ['createdFromUa'];
        yield 'message' => ['message'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(LearnLog::class, LearnLogCrudController::getEntityFqcn());
    }

    public function testConfigureFields(): void
    {
        $controller = new LearnLogCrudController();
        $fields = iterator_to_array($controller->configureFields('index'));

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(0, count($fields));
    }

    public function testControllerCanBeInstantiated(): void
    {
        $controller = new LearnLogCrudController();
        $this->assertInstanceOf(LearnLogCrudController::class, $controller);
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();

        // 提交空表单
        $form = $crawler->filter('form[name="LearnLog"]')->form();
        $client->submit($form);

        // 验证返回422状态码（表单验证失败）
        $this->assertResponseStatusCodeSame(422);

        // 验证响应内容包含必填字段错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringContainsString('This value should not be null', $responseContent);
    }
}
