<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainClassroomBundle\Service\RegistrationService;
use Tourze\TrainRecordBundle\Procedure\GetJobTrainingLearnRecordList;

/**
 * GetJobTrainingLearnRecordList 集成测试
 *
 * @internal
 */
#[CoversClass(GetJobTrainingLearnRecordList::class)]
#[RunTestsInSeparateProcesses]
final class GetJobTrainingLearnRecordListTest extends AbstractProcedureTestCase
{
    private GetJobTrainingLearnRecordList $procedure;

    protected function onSetUp(): void
    {
        // 不在这里初始化 procedure，在每个测试方法中单独初始化
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->procedure = self::getService(GetJobTrainingLearnRecordList::class);
        $this->assertInstanceOf(GetJobTrainingLearnRecordList::class, $this->procedure);
    }

    public function testExecuteReturnsArrayWithListKey(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟空的报名记录
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findUserRegistrations')
            ->with($user)
            ->willReturn([])
        ;

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnRecordList::class);

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertIsArray($result['list']);
    }

    public function testExecuteWithMockedDependencies(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟报名记录
        $registration = $this->createMockRegistration();
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findUserRegistrations')
            ->with($user)
            ->willReturn([$registration])
        ;

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnRecordList::class);

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertIsArray($result['list']);
        $this->assertCount(1, $result['list']);
        $this->assertIsArray($result['list'][0]);
        $this->assertEquals('123', $result['list'][0]['id']);
    }

    public function testExecuteWithEmptyRegistrations(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟空的报名记录
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findUserRegistrations')
            ->with($user)
            ->willReturn([])
        ;

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnRecordList::class);

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertEmpty($result['list']);
    }

    private function createMockRegistration(): object
    {
        return new class {
            /** @return array<string, mixed> */
            public function retrieveApiArray(): array
            {
                return [
                    'id' => '123',
                    'title' => 'Test Course',
                    'status' => 'active',
                ];
            }
        };
    }
}
