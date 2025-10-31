<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\FaceDetect;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * @internal
 */
#[CoversClass(LearnSession::class)]
#[RunTestsInSeparateProcesses]
final class LearnSessionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LearnSession();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $mockStudent = new class {
            public function getId(): string
            {
                return 'test-student-id';
            }
        };

        $mockRegistration = new class {
            public function getId(): string
            {
                return 'test-registration-id';
            }
        };

        $mockCourse = new class {
            public function getId(): string
            {
                return 'test-course-id';
            }

            public function getTitle(): string
            {
                return 'Test Course';
            }
        };

        $mockLesson = new class {
            public function getId(): string
            {
                return 'test-lesson-id';
            }

            public function getTitle(): string
            {
                return 'Test Lesson';
            }
        };

        $mockDevice = new class {
            public function getId(): string
            {
                return 'test-device-id';
            }
        };

        yield 'finished' => ['finished', true];
        yield 'active' => ['active', true];
        yield 'finishTime' => ['finishTime', new \DateTimeImmutable('2024-01-01 12:00:00')];
        yield 'currentDuration' => ['currentDuration', '3600.50'];
        yield 'totalDuration' => ['totalDuration', '7200.75'];
        yield 'effectiveDuration' => ['effectiveDuration', '6000.25'];
        yield 'sessionId' => ['sessionId', 'session-12345'];
        yield 'createdFromUa' => ['createdFromUa', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
        yield 'updatedFromUa' => ['updatedFromUa', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'];
        yield 'createdFromIp' => ['createdFromIp', '192.168.1.100'];
        yield 'updatedFromIp' => ['updatedFromIp', '192.168.1.101'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testAddAndRemoveLearnLog(): void
    {
        $entity = new LearnSession();
        $learnLog = new LearnLog();
        $entity->addLearnLog($learnLog);
        $this->assertCount(1, $entity->getLearnLogs());
        $this->assertTrue($entity->getLearnLogs()->contains($learnLog));
        $entity->removeLearnLog($learnLog);
        $this->assertCount(0, $entity->getLearnLogs());
    }

    public function testAddAndRemoveLearnBehavior(): void
    {
        $entity = new LearnSession();
        $behavior = new LearnBehavior();
        $entity->addLearnBehavior($behavior);
        $this->assertCount(1, $entity->getLearnBehaviors());
        $this->assertTrue($entity->getLearnBehaviors()->contains($behavior));
        // 移除
        $entity->removeLearnBehavior($behavior);
        $this->assertCount(0, $entity->getLearnBehaviors());
    }

    public function testIsFinishedProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值
        $this->assertFalse($entity->isFinished());
        $this->assertFalse($entity->getFinished());

        // 测试设置为完成
        $entity->setFinished(true);
        $this->assertTrue($entity->isFinished());
        $this->assertTrue($entity->getFinished());
    }

    public function testIsActiveProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值（默认为false）
        $this->assertFalse($entity->isActive());
        $this->assertFalse($entity->getActive());

        // 测试设置为活跃
        $entity->setActive(true);
        $this->assertTrue($entity->isActive());
        $this->assertTrue($entity->getActive());
    }

    public function testFinishTimeProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值
        $this->assertNull($entity->getFinishTime());

        // 测试设置完成时间
        $finishTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $entity->setFinishTime($finishTime);
        $this->assertSame($finishTime, $entity->getFinishTime());
    }

    public function testCurrentDurationProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值
        $this->assertEquals('0.00', $entity->getCurrentDuration());

        // 测试设置时长
        $duration = '3600.5';
        $entity->setCurrentDuration($duration);
        $this->assertEquals($duration, $entity->getCurrentDuration());
    }

    public function testTotalDurationProperty(): void
    {
        $entity = new LearnSession();
        // 测试字符串
        $entity->setTotalDuration('7200.75');
        $this->assertEquals('7200.75', $entity->getTotalDuration());

        // 测试浮点数
        $entity->setTotalDuration(3600.5);
        $this->assertEquals('3600.5', $entity->getTotalDuration());
    }

    public function testEffectiveDurationProperty(): void
    {
        $entity = new LearnSession();
        // 测试字符串
        $entity->setEffectiveDuration('6000.25');
        $this->assertEquals('6000.25', $entity->getEffectiveDuration());

        // 测试浮点数
        $entity->setEffectiveDuration(2400.75);
        $this->assertEquals('2400.75', $entity->getEffectiveDuration());
    }

    public function testSessionIdProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值
        $this->assertNull($entity->getSessionId());

        // 测试设置会话ID
        $sessionId = 'session-12345';
        $entity->setSessionId($sessionId);
        $this->assertEquals($sessionId, $entity->getSessionId());
    }

    public function testStudentProperty(): void
    {
        $entity = new LearnSession();
        $student = new InMemoryUser('test@example.com', null);
        $entity->setStudent($student);
        $this->assertSame($student, $entity->getStudent());
    }

    public function testLessonProperty(): void
    {
        $entity = new LearnSession();
        $lesson = new Lesson();
        $entity->setLesson($lesson);
        $this->assertSame($lesson, $entity->getLesson());
    }

    public function testRegistrationProperty(): void
    {
        $entity = new LearnSession();
        $registration = new Registration();
        $entity->setRegistration($registration);
        $this->assertSame($registration, $entity->getRegistration());
    }

    public function testDeviceProperty(): void
    {
        $entity = new LearnSession();
        // 测试默认值
        $this->assertNull($entity->getDevice());

        // 测试设置设备
        $device = new LearnDevice();
        $entity->setDevice($device);
        $this->assertSame($device, $entity->getDevice());
    }

    public function testFaceDetects(): void
    {
        $entity = new LearnSession();
        $faceDetect = new FaceDetect();

        // 测试添加
        $entity->addFaceDetect($faceDetect);
        $this->assertCount(1, $entity->getFaceDetects());
        $this->assertTrue($entity->getFaceDetects()->contains($faceDetect));

        // 测试移除
        $entity->removeFaceDetect($faceDetect);
        $this->assertCount(0, $entity->getFaceDetects());
    }

    public function testRetrieveApiArray(): void
    {
        $entity = new LearnSession();
        $entity->setCurrentDuration('1800');
        $entity->setFinished(true);
        $entity->setCreatedFromIp('192.168.1.100');

        $apiArray = $entity->retrieveApiArray();

        $this->assertIsArray($apiArray);
        $this->assertArrayHasKey('id', $apiArray);
        $this->assertArrayHasKey('firstLearnTime', $apiArray);
        $this->assertArrayHasKey('lastLearnTime', $apiArray);
        $this->assertArrayHasKey('createdFromIp', $apiArray);
        $this->assertArrayHasKey('currentDuration', $apiArray);
        $this->assertArrayHasKey('finished', $apiArray);

        $this->assertEquals('1800', $apiArray['currentDuration']);
        $this->assertTrue($apiArray['finished']);
        $this->assertEquals('192.168.1.100', $apiArray['createdFromIp']);
    }

    public function testRetrieveAdminArray(): void
    {
        $entity = new LearnSession();
        $adminArray = $entity->retrieveAdminArray();
        $this->assertIsArray($adminArray);
    }

    public function testToString(): void
    {
        $entity = new LearnSession();
        $student = new InMemoryUser('test@example.com', null);
        $lesson = new Lesson();
        // 使用反射设置lesson的title来避免复杂的mock
        $reflection = new \ReflectionClass($lesson);
        if ($reflection->hasProperty('title')) {
            $titleProperty = $reflection->getProperty('title');
            $titleProperty->setAccessible(true);
            $titleProperty->setValue($lesson, '测试课程');
        }

        $entity->setStudent($student);
        $entity->setLesson($lesson);

        $result = (string) $entity;
        $this->assertStringContainsString('学习会话', $result);
        $this->assertStringContainsString('test@example.com', $result);
    }
}
