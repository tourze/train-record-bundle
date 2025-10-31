<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure\Learn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoTimeUpdate;

/**
 * ReportJobTrainingCourseVideoTimeUpdate 集成测试
 *
 * @internal
 */
#[CoversClass(ReportJobTrainingCourseVideoTimeUpdate::class)]
#[RunTestsInSeparateProcesses]
final class ReportJobTrainingCourseVideoTimeUpdateTest extends AbstractProcedureTestCase
{
    private ReportJobTrainingCourseVideoTimeUpdate $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(ReportJobTrainingCourseVideoTimeUpdate::class);
    }

    public function testProcedureCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ReportJobTrainingCourseVideoTimeUpdate::class, $this->procedure);
    }

    public function testExecuteWithValidParameters(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = self::getService(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        $this->procedure->sessionId = 'test_session_id';
        $this->procedure->currentTime = '30';
        $this->procedure->duration = '3600';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        // 可能返回 '__message' 或者其他类型的响应
    }

    public function testParametersExist(): void
    {
        $reflection = new \ReflectionClass($this->procedure);

        // 检查所有参数是否存在
        $this->assertTrue($reflection->hasProperty('sessionId'));
        $this->assertTrue($reflection->hasProperty('currentTime'));
        $this->assertTrue($reflection->hasProperty('duration'));

        // 检查参数是否为 public
        $sessionIdProp = $reflection->getProperty('sessionId');
        $this->assertTrue($sessionIdProp->isPublic());

        $currentTimeProp = $reflection->getProperty('currentTime');
        $this->assertTrue($currentTimeProp->isPublic());

        $durationProp = $reflection->getProperty('duration');
        $this->assertTrue($durationProp->isPublic());
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
        $this->procedure->currentTime = '30';
        $this->procedure->duration = '3600';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('找不到学习记录');

        $this->procedure->execute();
    }
}
