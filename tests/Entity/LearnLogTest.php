<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;

/**
 * @internal
 */
#[CoversClass(LearnLog::class)]
#[RunTestsInSeparateProcesses]
final class LearnLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnLog();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'action' => ['action', LearnAction::START];
        yield 'createdFromUa' => ['createdFromUa', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'createdFromIp' => ['createdFromIp', '192.168.1.100'];
    }

    public function testIdProperty(): void
    {
        $entity = new LearnLog();
        // 测试默认值
        $this->assertEquals(0, $entity->getId());
    }

    public function testLearnSessionProperty(): void
    {
        $entity = new LearnLog();
        $session = new LearnSession();
        $entity->setLearnSession($session);
        $this->assertSame($session, $entity->getLearnSession());
    }

    public function testStudentProperty(): void
    {
        $entity = new LearnLog();
        $student = new InMemoryUser('test@example.com', null);
        $entity->setStudent($student);
        $this->assertSame($student, $entity->getStudent());
    }

    public function testRegistrationProperty(): void
    {
        $entity = new LearnLog();
        $registration = new Registration();
        $entity->setRegistration($registration);
        $this->assertSame($registration, $entity->getRegistration());
    }

    public function testLessonProperty(): void
    {
        $entity = new LearnLog();
        $lesson = new Lesson();
        $entity->setLesson($lesson);
        $this->assertSame($lesson, $entity->getLesson());
    }

    public function testActionProperty(): void
    {
        $entity = new LearnLog();
        $action = LearnAction::START;
        $entity->setAction($action);
        $this->assertEquals($action, $entity->getAction());
    }

    public function testCreatedFromUaProperty(): void
    {
        $entity = new LearnLog();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $entity->setCreatedFromUa($userAgent);
        $this->assertEquals($userAgent, $entity->getCreatedFromUa());
    }

    public function testCreateTimeProperty(): void
    {
        $entity = new LearnLog();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $entity->setCreateTime($createTime);
        $this->assertSame($createTime, $entity->getCreateTime());
    }

    public function testCreatedFromIpProperty(): void
    {
        $entity = new LearnLog();
        $ipAddress = '192.168.1.100';
        $entity->setCreatedFromIp($ipAddress);
        $this->assertEquals($ipAddress, $entity->getCreatedFromIp());
    }

    public function testToString(): void
    {
        $entity = new LearnLog();
        // 使用反射设置ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, 123);
        $this->assertEquals('123', (string) $entity);
    }

    public function testLogLevelProperty(): void
    {
        $entity = new LearnLog();
        // 测试默认值
        $this->assertNull($entity->getLogLevel());

        // 测试设置日志级别
        $logLevel = 'INFO';
        $entity->setLogLevel($logLevel);
        $this->assertEquals($logLevel, $entity->getLogLevel());
    }

    public function testMessageProperty(): void
    {
        $entity = new LearnLog();
        // 测试默认值
        $this->assertNull($entity->getMessage());

        // 测试设置消息
        $message = '用户开始学习课程';
        $entity->setMessage($message);
        $this->assertEquals($message, $entity->getMessage());
    }

    public function testContextProperty(): void
    {
        $entity = new LearnLog();
        // 测试默认值
        $this->assertNull($entity->getContext());

        // 测试设置上下文
        $context = ['userId' => '123', 'lessonId' => '456', 'duration' => 1800];
        $entity->setContext($context);
        $this->assertEquals($context, $entity->getContext());
    }

    public function testActionEnumProperty(): void
    {
        $entity = new LearnLog();
        $action = LearnAction::START;
        $entity->setAction($action);
        $this->assertEquals($action, $entity->getAction());

        // 测试其他动作
        $entity->setAction(LearnAction::PAUSE);
        $this->assertEquals(LearnAction::PAUSE, $entity->getAction());

        $entity->setAction(LearnAction::ENDED);
        $this->assertEquals(LearnAction::ENDED, $entity->getAction());
    }
}
