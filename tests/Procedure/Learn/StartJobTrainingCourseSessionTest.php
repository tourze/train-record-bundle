<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure\Learn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainRecordBundle\Procedure\Learn\StartJobTrainingCourseSession;

/**
 * StartJobTrainingCourseSession 集成测试
 *
 * @internal
 */
#[CoversClass(StartJobTrainingCourseSession::class)]
#[RunTestsInSeparateProcesses]
final class StartJobTrainingCourseSessionTest extends AbstractProcedureTestCase
{
    private StartJobTrainingCourseSession $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(StartJobTrainingCourseSession::class);
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->assertInstanceOf(StartJobTrainingCourseSession::class, $this->procedure);
    }

    public function testExecuteWithValidParameters(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->registrationId = 'test_registration_id';
        $this->procedure->lessonId = 'test_lesson_id';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        // 返回值应该包含学习会话的信息
    }

    public function testParametersExist(): void
    {
        $reflection = new \ReflectionClass($this->procedure);

        // 检查所有参数是否存在
        $this->assertTrue($reflection->hasProperty('registrationId'));
        $this->assertTrue($reflection->hasProperty('lessonId'));

        // 检查参数是否为 public
        $registrationIdProp = $reflection->getProperty('registrationId');
        $this->assertTrue($registrationIdProp->isPublic());

        $lessonIdProp = $reflection->getProperty('lessonId');
        $this->assertTrue($lessonIdProp->isPublic());

        // 检查参数类型
        $registrationIdType = $registrationIdProp->getType();
        $lessonIdType = $lessonIdProp->getType();

        if ($registrationIdType instanceof \ReflectionNamedType) {
            $this->assertEquals('string', $registrationIdType->getName());
        }

        if ($lessonIdType instanceof \ReflectionNamedType) {
            $this->assertEquals('string', $lessonIdType->getName());
        }
    }

    public function testExecuteThrowsExceptionWhenRegistrationNotFound(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->registrationId = 'nonexistent_registration';
        $this->procedure->lessonId = 'test_lesson_id';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('找不到报名信息');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenLessonNotFound(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->registrationId = 'test_registration_id';
        $this->procedure->lessonId = 'nonexistent_lesson';

        $this->expectException(ApiException::class);
        $this->procedure->execute();
    }
}
