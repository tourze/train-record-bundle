<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure\Learn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoPause;

/**
 * ReportJobTrainingCourseVideoPause 集成测试
 *
 * @internal
 */
#[CoversClass(ReportJobTrainingCourseVideoPause::class)]
#[RunTestsInSeparateProcesses]
final class ReportJobTrainingCourseVideoPauseTest extends AbstractProcedureTestCase
{
    private ReportJobTrainingCourseVideoPause $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(ReportJobTrainingCourseVideoPause::class);
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ReportJobTrainingCourseVideoPause::class, $this->procedure);
    }

    public function testExecuteWithValidSession(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->sessionId = 'test_session_id';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('__message', $result);
        $this->assertEquals('上报成功', $result['__message']);
    }

    public function testExecuteThrowsExceptionWhenSessionNotFound(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->sessionId = 'nonexistent_session';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('找不到学习记录');

        $this->procedure->execute();
    }

    public function testSessionIdParameterExists(): void
    {
        $reflection = new \ReflectionClass($this->procedure);
        $this->assertTrue($reflection->hasProperty('sessionId'));

        $property = $reflection->getProperty('sessionId');
        $this->assertTrue($property->isPublic());
    }
}
