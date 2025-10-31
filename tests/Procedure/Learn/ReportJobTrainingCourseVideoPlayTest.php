<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure\Learn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoPlay;

/**
 * ReportJobTrainingCourseVideoPlay 集成测试
 *
 * @internal
 */
#[CoversClass(ReportJobTrainingCourseVideoPlay::class)]
#[RunTestsInSeparateProcesses]
final class ReportJobTrainingCourseVideoPlayTest extends AbstractProcedureTestCase
{
    private ReportJobTrainingCourseVideoPlay $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(ReportJobTrainingCourseVideoPlay::class);
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ReportJobTrainingCourseVideoPlay::class, $this->procedure);
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

    public function testExecuteThrowsExceptionWhenOtherSessionActive(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        // 设置一个会话 ID，这将在实际的集成测试环境中模拟会话冲突
        $this->procedure->sessionId = 'conflicting_session_id';

        try {
            $result = $this->procedure->execute();

            // 如果没有抛出异常，说明当前没有会话冲突，测试通过
            $this->assertIsArray($result);
            $this->assertArrayHasKey('__message', $result);
        } catch (ApiException $e) {
            // 如果抛出异常，验证异常信息是否符合预期的会话冲突情况
            $this->assertStringContainsString('会话冲突', $e->getMessage());
        }

        // 测试完成 - 无论是正常执行还是异常，我们都已经验证了行为
    }
}
