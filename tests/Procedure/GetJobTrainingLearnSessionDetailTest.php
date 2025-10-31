<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainClassroomBundle\Service\RegistrationService;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Procedure\GetJobTrainingLearnSessionDetail;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * GetJobTrainingLearnSessionDetail 集成测试
 *
 * @internal
 */
#[CoversClass(GetJobTrainingLearnSessionDetail::class)]
#[RunTestsInSeparateProcesses]
final class GetJobTrainingLearnSessionDetailTest extends AbstractProcedureTestCase
{
    private GetJobTrainingLearnSessionDetail $procedure;

    protected function onSetUp(): void
    {
        // 不在这里初始化 procedure，在每个测试方法中单独初始化
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->procedure = self::getService(GetJobTrainingLearnSessionDetail::class);
        $this->assertInstanceOf(GetJobTrainingLearnSessionDetail::class, $this->procedure);
    }

    public function testExecuteWithValidRegistrationId(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟注册信息
        $registration = $this->createMockRegistrationWithCourse($user);
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findById')
            ->with('reg123')
            ->willReturn($registration)
        ;

        // 模拟学习会话
        $sessions = [$this->createMockLearnSession()];
        $learnSessionRepository = $this->createMock(LearnSessionRepository::class);
        $learnSessionRepository->method('findBy')
            ->with(['registration' => $registration])
            ->willReturn($sessions)
        ;

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);
        self::getContainer()->set(LearnSessionRepository::class, $learnSessionRepository);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnSessionDetail::class);
        $procedure->registrationId = 'reg123';

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('course', $result);
        $this->assertArrayHasKey('sessions', $result);
        $this->assertIsArray($result['sessions']);
        $this->assertCount(1, $result['sessions']);
    }

    public function testExecuteThrowsExceptionWhenRegistrationNotFound(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟注册服务返回null
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findById')
            ->with('invalid_reg')
            ->willReturn(null)
        ;

        $learnSessionRepository = $this->createMock(LearnSessionRepository::class);

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);
        self::getContainer()->set(LearnSessionRepository::class, $learnSessionRepository);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnSessionDetail::class);
        $procedure->registrationId = 'invalid_reg';

        $this->expectException(ApiException::class);
        $procedure->execute();
    }

    public function testExecuteWithEmptySessionsList(): void
    {
        // 模拟用户
        $user = $this->createMock(UserInterface::class);

        // 模拟安全服务
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // 模拟注册信息
        $registration = $this->createMockRegistrationWithCourse($user);
        $registrationService = $this->createMock(RegistrationService::class);
        $registrationService->method('findById')
            ->with('reg123')
            ->willReturn($registration)
        ;

        // 模拟空的学习会话
        $learnSessionRepository = $this->createMock(LearnSessionRepository::class);
        $learnSessionRepository->method('findBy')
            ->with(['registration' => $registration])
            ->willReturn([])
        ;

        // 在获取服务前注入Mock依赖
        self::getContainer()->set(Security::class, $security);
        self::getContainer()->set(RegistrationService::class, $registrationService);
        self::getContainer()->set(LearnSessionRepository::class, $learnSessionRepository);

        // 从容器中获取服务实例
        $procedure = self::getService(GetJobTrainingLearnSessionDetail::class);
        $procedure->registrationId = 'reg123';

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sessions', $result);
        $this->assertEmpty($result['sessions']);
    }

    public function testRegistrationIdParameterValidation(): void
    {
        $procedure = self::getService(GetJobTrainingLearnSessionDetail::class);

        // 测试参数存在性
        $reflection = new \ReflectionClass($procedure);
        $this->assertTrue($reflection->hasProperty('registrationId'));

        $property = $reflection->getProperty('registrationId');
        $this->assertTrue($property->isPublic());

        // 验证参数类型
        $type = $property->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertEquals('string', $type->getName());
    }

    private function createMockRegistrationWithCourse(UserInterface $student): Registration
    {
        $registration = $this->createMock(Registration::class);
        $registration->method('getStudent')->willReturn($student);
        $registration->method('retrieveApiArray')->willReturn([
            'id' => 'reg123',
            'title' => 'Test Registration',
            'status' => 'active',
        ]);

        $course = $this->createMock(Course::class);
        $course->method('retrieveApiArray')->willReturn([
            'id' => 'course456',
            'title' => 'Test Course',
            'description' => 'Test Description',
        ]);
        $registration->method('getCourse')->willReturn($course);

        return $registration;
    }

    private function createMockLearnSession(): object
    {
        return new class {
            /** @return array<string, mixed> */
            public function retrieveApiArray(): array
            {
                return [
                    'id' => 'session789',
                    'duration' => 3600,
                    'progress' => 0.5,
                    'finished' => false,
                ];
            }
        };
    }
}
