<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\TrainRecordBundle\Controller\Admin\LearnBehaviorCrudController;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;

/**
 * @internal
  */
#[CoversClass(LearnBehaviorCrudController::class)]
#[RunTestsInSeparateProcesses]
final class LearnBehaviorCrudControllerTest extends AbstractTrainRecordAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<LearnBehavior>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(LearnBehaviorCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'session' => ['学习会话'];
        yield 'behaviorType' => ['行为类型'];
        yield 'userId' => ['用户ID'];
        yield 'videoTimestamp' => ['视频时间戳'];
        yield 'ipAddress' => ['IP地址'];
        yield 'isSuspicious' => ['可疑行为'];
        yield 'createTime' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'session' => ['session'];
        yield 'behaviorType' => ['behaviorType'];
        yield 'behaviorData' => ['behaviorData'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'session' => ['session'];
        yield 'behaviorType' => ['behaviorType'];
        yield 'behaviorData' => ['behaviorData'];
    }

    public function testConfigureFields(): void
    {
        $controller = new LearnBehaviorCrudController();
        $fields = iterator_to_array($controller->configureFields('index'));

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(0, count($fields));
    }

    public function testControllerCanBeInstantiated(): void
    {
        $controller = new LearnBehaviorCrudController();
        $this->assertInstanceOf(LearnBehaviorCrudController::class, $controller);
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));

        // Submit empty form to trigger validation errors
        $form = $crawler->selectButton('Create')->form([
            'LearnBehavior[behaviorType]' => '',  // Empty required field
        ]);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        // Check for validation error messages
        $errorElements = $crawler->filter('.invalid-feedback, .form-error-message, .error-message');
        $this->assertGreaterThan(0, $errorElements->count(), 'Should display validation error messages');

        // Verify error message contains expected validation text
        $errorText = $errorElements->first()->text();
        $this->assertStringContainsString('should not be blank', $errorText);
    }
}
