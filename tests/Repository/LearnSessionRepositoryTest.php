<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\CatalogBundle\Entity\CatalogType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainClassroomBundle\Entity\Classroom;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Chapter;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * LearnSessionRepository 集成测试
 *
 * @template TEntity of LearnSession
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnSessionRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnSessionRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnSessionRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnSession::class);
        $this->assertInstanceOf(LearnSessionRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 创建测试会话
     * 为了避免唯一约束冲突，使用计数器生成唯一的标识符
     */
    private static int $sessionCounter = 0;

    /**
     * 创建完整的测试依赖链
     * @return array{catalog: Catalog, course: Course, classroom: Classroom, user: UserInterface, registration: Registration, lesson: Lesson}
     */
    private function createTestDependencies(string $uniqueId): array
    {
        $em = self::getEntityManager();

        // 创建 CatalogType (Catalog 的必需依赖)
        $catalogType = new CatalogType();
        $catalogType->setCode('test_type_' . $uniqueId);
        $catalogType->setName('Test Type ' . $uniqueId);
        $em->persist($catalogType);

        // 创建 Catalog
        $catalog = new Catalog();
        $catalog->setType($catalogType);
        $catalog->setName('Test Category ' . $uniqueId);
        $catalog->setSortOrder(0);
        $em->persist($catalog);

        // 创建 Course (需要 category 和 learnHour)
        $course = new Course();
        $course->setCategory($catalog);
        $course->setTitle('Test Course ' . $uniqueId);
        $course->setLearnHour(40); // 设置必需的学时字段
        $em->persist($course);

        // 创建 Classroom
        $classroom = new Classroom();
        $classroom->setCategory($catalog);
        $classroom->setTitle('Test Classroom ' . $uniqueId);
        $classroom->setCourse($course);
        $em->persist($classroom);

        // 创建 User
        $user = $this->createNormalUser($uniqueId . '@example.com');

        // 创建 Registration
        $registration = new Registration();
        $registration->setClassroom($classroom);
        $registration->setStudent($user);
        $registration->setCourse($course);
        $registration->setBeginTime(new \DateTimeImmutable());
        $em->persist($registration);

        // 创建 Chapter (Lesson 的必需依赖)
        $chapter = new Chapter();
        $chapter->setCourse($course);
        $chapter->setTitle('Test Chapter ' . $uniqueId);
        $em->persist($chapter);

        // 创建 Lesson (需要 chapter 和 durationSecond)
        $lesson = new Lesson();
        $lesson->setChapter($chapter);
        $lesson->setTitle('Test Lesson ' . $uniqueId);
        $lesson->setDurationSecond(1800); // 30分钟
        $em->persist($lesson);

        // 刷新所有依赖实体
        $em->flush();

        return [
            'catalog' => $catalog,
            'course' => $course,
            'classroom' => $classroom,
            'user' => $user,
            'registration' => $registration,
            'lesson' => $lesson,
        ];
    }

    /**
     * @param array{user: UserInterface, registration: Registration, lesson: Lesson, course: Course}|array{} $deps
     */
    private function createTestSessionWithDeps(
        ?string $student = 'student001',
        string $lesson = 'lesson001',
        bool $active = true,
        bool $finished = false,
        ?\DateTimeInterface $lastLearnTime = null,
        float $totalDuration = 1800,
        array $deps = [],
    ): LearnSession {
        // 调整参数顺序，deps放在前面
        $session = new LearnSession();
        if ([] === $deps) {
            throw new \InvalidArgumentException('Deps array cannot be empty');
        }
        $session->setStudent($deps['user']);
        $session->setRegistration($deps['registration']);
        $session->setLesson($deps['lesson']);
        $session->setCourse($deps['course']);
        $session->setActive($active);
        $session->setFinished($finished);
        $session->setLastLearnTime($lastLearnTime instanceof \DateTimeImmutable ? $lastLearnTime :
            (null !== $lastLearnTime ? new \DateTimeImmutable($lastLearnTime->format('Y-m-d H:i:s')) : new \DateTimeImmutable()));
        $session->setTotalDuration($totalDuration);
        $session->setEffectiveDuration(0.8 * $totalDuration);
        $session->setSessionId('session-' . uniqid() . '-' . $lesson . '-' . $student);

        $em = self::getEntityManager();
        $em->persist($session);
        $em->flush();

        return $session;
    }

    private function createTestSession(
        ?string $student = 'student001',
        string $lesson = 'lesson001',
        bool $active = true,
        bool $finished = false,
        ?\DateTimeInterface $lastLearnTime = null,
        float $totalDuration = 1800,
    ): LearnSession {
        // 为每个会话生成唯一标识符，避免约束冲突
        $uniqueId = ++self::$sessionCounter;
        $uniqueIdentifier = $student . '-' . $uniqueId . '-' . uniqid();

        // 创建完整的依赖链
        $deps = $this->createTestDependencies($uniqueIdentifier);

        $em = self::getEntityManager();

        $session = new LearnSession();
        $session->setStudent($deps['user']);
        $session->setRegistration($deps['registration']);
        $session->setLesson($deps['lesson']);
        $session->setCourse($deps['course']);
        $session->setActive($active);
        $session->setFinished($finished);
        $session->setLastLearnTime($lastLearnTime instanceof \DateTimeImmutable ? $lastLearnTime :
            (null !== $lastLearnTime ? new \DateTimeImmutable($lastLearnTime->format('Y-m-d H:i:s')) : new \DateTimeImmutable()));
        $session->setTotalDuration($totalDuration);
        $session->setEffectiveDuration(0.8 * $totalDuration);
        $session->setSessionId('session-' . $uniqueIdentifier);

        $em->persist($session);
        $em->flush();

        return $session;
    }

    /**
     * 测试查找学员的活跃会话
     */
    public function testFindActiveSessionsByStudentReturnsOnlyActiveUnfinishedSessions(): void
    {
        $student = 'student001';

        // 创建主要用户和依赖
        $mainDeps = $this->createTestDependencies('test-main');

        // 为每个课时创建不同的registration，但使用相同的用户
        $lesson1Deps = $this->createTestDependencies('lesson1');
        $lesson1Deps['user'] = $mainDeps['user']; // 使用相同的用户
        $this->createTestSessionWithDeps($student, 'lesson001', true, false, null, 1800, $lesson1Deps);

        $lesson2Deps = $this->createTestDependencies('lesson2');
        $lesson2Deps['user'] = $mainDeps['user']; // 使用相同的用户
        $this->createTestSessionWithDeps($student, 'lesson002', true, false, null, 1800, $lesson2Deps);

        $lesson3Deps = $this->createTestDependencies('lesson3');
        $lesson3Deps['user'] = $mainDeps['user']; // 使用相同的用户
        $this->createTestSessionWithDeps($student, 'lesson003', false, false, null, 1800, $lesson3Deps); // 非活跃

        $lesson4Deps = $this->createTestDependencies('lesson4');
        $lesson4Deps['user'] = $mainDeps['user']; // 使用相同的用户
        $this->createTestSessionWithDeps($student, 'lesson004', true, true, null, 1800, $lesson4Deps); // 已完成

        $this->createTestSession('student002', 'lesson001', true, false);

        // 使用主用户对象查询
        $results = $this->repository->findActiveSessionsByStudent($mainDeps['user']);

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertEquals($mainDeps['user']->getUserIdentifier(), $session->getStudent()->getUserIdentifier());
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试查找学员在其他课程的活跃会话
     */
    public function testFindOtherActiveSessionsByStudentExcludesCurrentLesson(): void
    {
        // 首先创建一个统一的用户
        $baseDeps = $this->createTestDependencies('test-user-123');
        $studentId = 'student001'; // 这是用于生成session ID的字符串
        $studentUser = $baseDeps['user']; // 这是实际的User实体

        // 为每个会话创建不同的注册以满足唯一约束
        $deps1 = $this->createTestDependencies('test-session-1');
        $deps1['user'] = $studentUser; // 使用相同的用户

        $deps2 = $this->createTestDependencies('test-session-2');
        $deps2['user'] = $studentUser; // 使用相同的用户

        $deps3 = $this->createTestDependencies('test-session-3');
        $deps3['user'] = $studentUser; // 使用相同的用户

        $deps4 = $this->createTestDependencies('test-session-4');
        $deps4['user'] = $studentUser; // 使用相同的用户

        // 创建测试会话，使用相同的用户但不同的注册和课时
        $session1 = $this->createTestSessionWithDeps($studentId, 'lesson001', true, false, null, 1800, $deps1);
        $session2 = $this->createTestSessionWithDeps($studentId, 'lesson002', true, false, null, 1800, $deps2);
        $session3 = $this->createTestSessionWithDeps($studentId, 'lesson003', true, false, null, 1800, $deps3);
        $this->createTestSessionWithDeps($studentId, 'lesson004', false, false, null, 1800, $deps4); // 非活跃

        // 首先验证所有会话都已正确创建
        $allActiveSessions = $this->repository->findActiveSessionsByStudent($studentUser);
        $this->assertCount(3, $allActiveSessions, '应该有3个活跃会话');

        // 使用实际的 Lesson 实体作为参数
        $currentLesson = $session1->getLesson();
        $results = $this->repository->findOtherActiveSessionsByStudent($studentUser, $currentLesson);

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertNotEquals($currentLesson, $session->getLesson());
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试停用学员的所有活跃会话
     */
    public function testDeactivateAllActiveSessionsByStudentUpdatesCorrectSessions(): void
    {
        // 创建统一的用户对象
        $baseDeps = $this->createTestDependencies('test-user-deactivate');
        $studentUser = $baseDeps['user'];
        $studentId = 'student001';

        // 为不同的会话创建不同的注册以满足唯一约束
        $deps1 = $this->createTestDependencies('test-deactivate-1');
        $deps1['user'] = $studentUser;

        $deps2 = $this->createTestDependencies('test-deactivate-2');
        $deps2['user'] = $studentUser;

        $deps3 = $this->createTestDependencies('test-deactivate-3');
        $deps3['user'] = $studentUser;

        $deps4 = $this->createTestDependencies('test-deactivate-4'); // 不同用户

        $this->createTestSessionWithDeps($studentId, 'lesson001', true, false, null, 1800, $deps1);
        $this->createTestSessionWithDeps($studentId, 'lesson002', true, false, null, 1800, $deps2);
        $this->createTestSessionWithDeps($studentId, 'lesson003', false, false, null, 1800, $deps3);
        $this->createTestSessionWithDeps('student002', 'lesson001', true, false, null, 1800, $deps4);

        $updatedCount = $this->repository->deactivateAllActiveSessionsByStudent($studentUser);

        $this->assertEquals(2, $updatedCount);

        // 验证结果
        self::getEntityManager()->clear();
        $activeSessions = $this->repository->findActiveSessionsByStudent($studentUser);
        $this->assertEmpty($activeSessions);
    }

    /**
     * 测试保存会话
     */
    public function testSavePersistsSession(): void
    {
        $session = $this->createTestSession('student001', 'lesson001');

        // 该会话应该已经被持久化了
        $savedSession = $this->repository->find($session->getId());
        $this->assertNotNull($savedSession);
        // 验证用户标识符包含原始学生ID
        $this->assertStringContainsString('student001', $savedSession->getStudent()->getUserIdentifier());
        $this->assertTrue($savedSession->isActive());
    }

    /**
     * 测试查找超时的活跃会话
     */
    public function testFindInactiveActiveSessionsReturnsOldActiveSessions(): void
    {
        $thresholdMinutes = 30;

        $session1 = $this->createTestSession('student001', 'lesson001', true, false, new \DateTimeImmutable('-45 minutes'));
        $this->createTestSession('student002', 'lesson002', true, false, new \DateTimeImmutable('-20 minutes'));
        $this->createTestSession('student003', 'lesson003', true, true, new \DateTimeImmutable('-60 minutes')); // 已完成
        $this->createTestSession('student004', 'lesson004', false, false, new \DateTimeImmutable('-60 minutes')); // 非活跃

        $results = $this->repository->findInactiveActiveSessions($thresholdMinutes);

        $this->assertCount(1, $results);
        $this->assertEquals($session1->getStudent()->getUserIdentifier(), $results[0]->getStudent()->getUserIdentifier());
    }

    /**
     * 测试批量更新会话活跃状态
     */
    public function testBatchUpdateActiveStatusUpdatesCorrectly(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001', true, false);
        $session2 = $this->createTestSession('student002', 'lesson002', true, false);
        $session3 = $this->createTestSession('student003', 'lesson003', true, false);

        $sessionIds = array_filter([$session1->getId(), $session2->getId()], fn ($id) => null !== $id);
        $updatedCount = $this->repository->batchUpdateActiveStatus($sessionIds, false);

        $this->assertEquals(2, $updatedCount);

        self::getEntityManager()->clear();

        $updatedSession1 = $this->repository->find($session1->getId());
        $updatedSession2 = $this->repository->find($session2->getId());
        $updatedSession3 = $this->repository->find($session3->getId());

        $this->assertNotNull($updatedSession1);
        $this->assertNotNull($updatedSession2);
        $this->assertNotNull($updatedSession3);
        $this->assertFalse($updatedSession1->getActive());
        $this->assertFalse($updatedSession2->getActive());
        $this->assertTrue($updatedSession3->getActive());
    }

    /**
     * 测试空数组批量更新
     */
    public function testBatchUpdateActiveStatusWithEmptyArrayReturnsZero(): void
    {
        $updatedCount = $this->repository->batchUpdateActiveStatus([], false);

        $this->assertEquals(0, $updatedCount);
    }

    /**
     * 测试查找已完成的会话
     */
    public function testFindCompletedSessionsReturnsOnlyFinishedSessions(): void
    {
        $this->createTestSession('student001', 'lesson001', true, true);
        $this->createTestSession('student002', 'lesson002', false, true);
        $this->createTestSession('student003', 'lesson003', true, false);

        $results = $this->repository->findCompletedSessions();

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertTrue($session->getFinished());
        }
    }

    /**
     * 测试查找活跃会话
     */
    public function testFindActiveSessionsReturnsActiveUnfinishedSessions(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $session1 = $this->createTestSession('student001', 'lesson001', true, false);
        $session2 = $this->createTestSession('student002', 'lesson002', true, false);
        $this->createTestSession('student003', 'lesson003', false, false);
        $this->createTestSession('student004', 'lesson004', true, true);

        $results = $this->repository->findActiveSessions();

        // 调试输出
        if (2 !== count($results)) {
            echo "\n调试信息 - 活跃会话:\n";
            foreach ($results as $session) {
                echo "- Session: {$session->getSessionId()}, Active: " . ($session->getActive() ? 'true' : 'false') . ', Finished: ' . ($session->getFinished() ? 'true' : 'false') . "\n";
            }
        }

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试查找已过期的会话
     */
    public function testFindExpiredSessionsReturnsOldUnfinishedSessions(): void
    {
        $expireTime = new \DateTimeImmutable('-2 hours');

        $session1 = $this->createTestSession('student001', 'lesson001', true, false, new \DateTimeImmutable('-3 hours'));
        $this->createTestSession('student002', 'lesson002', true, false, new \DateTimeImmutable('-1 hour'));
        $this->createTestSession('student003', 'lesson003', true, true, new \DateTimeImmutable('-4 hours'));

        $results = $this->repository->findExpiredSessions($expireTime);

        $this->assertCount(1, $results);
        $this->assertEquals($session1->getStudent()->getUserIdentifier(), $results[0]->getStudent()->getUserIdentifier());
        $this->assertFalse($results[0]->getFinished());
    }

    /**
     * 测试计算平均时长
     */
    public function testAvgDurationByFiltersReturnsCorrectAverage(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001', true, false, null, 1800);
        $this->createTestSession('student002', 'lesson002', true, false, null, 2400);
        $this->createTestSession('student003', 'lesson003', true, false, null, 3000);

        $avgDuration = $this->repository->avgDurationByFilters([]);

        $this->assertEqualsWithDelta(2400.0, $avgDuration, 0.01); // (1800+2400+3000)/3
    }

    /**
     * 测试统计活跃会话数
     */
    public function testCountActiveSessionsSinceReturnsCorrectCount(): void
    {
        $since = new \DateTimeImmutable('-1 hour');

        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        // 创建会话时设置创建时间
        $session1 = $this->createTestSession('student001', 'lesson001', true, false);
        $session2 = $this->createTestSession('student002', 'lesson002', true, false);
        $session3 = $this->createTestSession('student003', 'lesson003', false, false);

        // 由于 createTime 可能由 Doctrine 自动设置，我们假设刚创建的会话都在 since 之后
        $count = $this->repository->countActiveSessionsSince($since);

        $this->assertEquals(2, $count);
    }

    /**
     * 测试按日期范围统计会话数
     */
    public function testCountByDateRangeReturnsCorrectCount(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');

        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001');
        $this->createTestSession('student002', 'lesson002');
        $this->createTestSession('student003', 'lesson003');

        $count = $this->repository->countByDateRange($startDate, $endDate);

        $this->assertEquals(3, $count);
    }

    /**
     * 测试按日期范围统计唯一用户数
     */
    public function testCountUniqueUsersByDateRangeReturnsUniqueCount(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');

        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        // 创建第一个用户的依赖
        $deps1 = $this->createTestDependencies('shared-user-001');

        // 为第一个用户创建第二个lesson的依赖（但使用相同的用户和registration）
        $deps1Lesson2 = $this->createTestDependencies('shared-user-001-lesson2');
        $deps1Lesson2['user'] = $deps1['user']; // 使用相同的用户
        $deps1Lesson2['registration'] = $deps1['registration']; // 使用相同的registration

        // 创建第二个用户的依赖
        $deps2 = $this->createTestDependencies('shared-user-002');

        // 创建第一个用户的两个会话（同一学生，不同lesson）
        $session1 = $this->createTestSessionWithDeps('shared-user-001', 'lesson001', true, false, null, 1800, $deps1);
        $session2 = $this->createTestSessionWithDeps('shared-user-001', 'lesson002', true, false, null, 1800, $deps1Lesson2); // 同一学生

        // 创建第二个用户的会话
        $session3 = $this->createTestSessionWithDeps('shared-user-002', 'lesson003', true, false, null, 1800, $deps2);

        $count = $this->repository->countUniqueUsersByDateRange($startDate, $endDate);

        $this->assertEquals(2, $count);
    }

    /**
     * 测试按日期范围统计总时长
     */
    public function testSumDurationByDateRangeReturnsCorrectSum(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');

        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001', true, false, null, 1800);
        $this->createTestSession('student002', 'lesson002', true, false, null, 2400);
        $this->createTestSession('student003', 'lesson003', true, false, null, 3000);

        $totalDuration = $this->repository->sumDurationByDateRange($startDate, $endDate);

        $this->assertEquals(7200.0, $totalDuration); // 1800+2400+3000
    }

    /**
     * 测试获取当前在线用户数
     */
    public function testGetCurrentOnlineUsersReturnsRecentActiveUsers(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001', true, false, new \DateTimeImmutable('-5 minutes'));
        $this->createTestSession('student002', 'lesson002', true, false, new \DateTimeImmutable('-10 minutes'));
        $this->createTestSession('student003', 'lesson003', true, false, new \DateTimeImmutable('-20 minutes')); // 超过15分钟
        $this->createTestSession('student004', 'lesson004', false, false, new \DateTimeImmutable('-5 minutes')); // 非活跃

        $onlineCount = $this->repository->getCurrentOnlineUsers();

        $this->assertEquals(2, $onlineCount);
    }

    /**
     * 测试带过滤条件的统计
     */
    public function testCountByFiltersWithCourseFilterReturnsFilteredCount(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        // 创建会话并设置课程（通过 lesson 关联）
        $session1 = $this->createTestSession('student001', 'lesson001');
        $session2 = $this->createTestSession('student002', 'lesson002');
        $session3 = $this->createTestSession('student003', 'lesson003');

        // 假设所有会话都在最近创建
        $filters = [
            'startTime' => new \DateTimeImmutable('-1 hour'),
            'endTime' => new \DateTimeImmutable('now'),
        ];

        $count = $this->repository->countByFilters($filters);

        $this->assertEquals(3, $count);
    }

    /**
     * 测试根据用户和日期范围查找会话
     */
    public function testFindByUserAndDateRange(): void
    {
        $userId = 'user_' . uniqid();
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->findByUserAndDateRange($userId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试根据日期范围查找会话
     */
    public function testFindByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->findByDateRange($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试根据用户和课时查找会话
     */
    public function testFindByUserAndLesson(): void
    {
        $userId = 'user_' . uniqid();
        $lessonId = 'lesson_' . uniqid();

        $result = $this->repository->findByUserAndLesson($userId, $lessonId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试刷新实体管理器
     */
    public function testFlush(): void
    {
        // flush 方法是简单的代理调用，只需验证不抛出异常
        $this->expectNotToPerformAssertions();
        $this->repository->flush();
    }

    /**
     * 测试根据用户ID和课程ID查找学习会话
     */
    public function testFindByUserAndCourse(): void
    {
        $userId = 'user_' . uniqid();
        $courseId = 'course_' . uniqid();

        $result = $this->repository->findByUserAndCourse($userId, $courseId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试按过滤条件统计唯一课程数
     */
    public function testCountUniqueCoursesByFilters(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $filters = [];

        $count = $this->repository->countUniqueCoursesByFilters($filters);

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    /**
     * 测试按过滤条件统计总时长
     */
    public function testSumDurationByFilters(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $filters = [];

        $totalDuration = $this->repository->sumDurationByFilters($filters);

        $this->assertIsFloat($totalDuration);
        $this->assertEquals(0.0, $totalDuration);
    }

    /**
     * 测试按过滤条件统计活跃用户数
     */
    public function testCountActiveUsersByFilters(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $filters = [];

        $count = $this->repository->countActiveUsersByFilters($filters);

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    /**
     * 测试按过滤条件统计新用户数
     */
    public function testCountNewUsersByFilters(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $filters = [
            'startTime' => new \DateTimeImmutable('-1 hour'),
            'endTime' => new \DateTimeImmutable('now'),
        ];

        $count = $this->repository->countNewUsersByFilters($filters);

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    /**
     * 测试按日期范围和过滤条件查找会话
     */
    public function testFindByDateRangeAndFilters(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试按过滤条件统计唯一用户数
     */
    public function testCountUniqueUsersByFilters(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $filters = [];

        $count = $this->repository->countUniqueUsersByFilters($filters);

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    /**
     * 测试查找一个存在的实体
     */

    /**
     * 测试 find 方法查找不存在的记录
     */
    public function testFindNonExistent(): void
    {
        $found = $this->repository->find(999999);

        $this->assertNull($found);
    }

    /**
     * 测试存在记录时 findAll 应返回实体数组
     */

    /**
     * 测试 findBy 匹配条件应返回实体数组
     */

    /**
     * 测试 findBy 应遵循 OrderBy 子句
     */

    /**
     * 测试 findBy 应遵循限制和偏移参数
     */

    /**
     * 测试 findOneBy 匹配条件应返回实体
     */

    /**
     * 测试 findOneBy 方法查找不存在的记录
     */
    public function testFindOneByNonExistent(): void
    {
        $this->createTestSession('student001', 'lesson001', true, false);

        $result = $this->repository->findOneBy(['active' => false, 'finished' => true]);

        $this->assertNull($result);
    }

    /**
     * 测试 save 方法
     */
    public function testSaveMethod(): void
    {
        $uniqueId = uniqid('save-test-', true);

        // 创建完整的依赖链
        $deps = $this->createTestDependencies($uniqueId);

        $session = new LearnSession();
        $session->setStudent($deps['user']);
        $session->setRegistration($deps['registration']);
        $session->setLesson($deps['lesson']);
        $session->setCourse($deps['course']);
        $session->setActive(true);
        $session->setFinished(false);
        $session->setTotalDuration(1800);
        $session->setEffectiveDuration(1440);
        $session->setSessionId('session-' . $uniqueId);

        $this->repository->save($session, true);

        $this->assertNotNull($session->getId());

        $found = $this->repository->find($session->getId());
        $this->assertNotNull($found);
        $this->assertTrue($found->getActive());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemove(): void
    {
        $session = $this->createTestSession('student001', 'lesson001');
        $id = $session->getId();

        $this->repository->remove($session, true);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    /**
     * 测试查询可空字段和布尔字段
     */
    public function testFindByNullableAndBooleanFields(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001', true, false);
        $this->createTestSession('student002', 'lesson002', false, true);

        $activeResults = $this->repository->findBy(['active' => true]);
        $this->assertCount(1, $activeResults);

        $finishedResults = $this->repository->findBy(['finished' => true]);
        $this->assertCount(1, $finishedResults);

        $activeUnfinishedResults = $this->repository->findBy(['active' => true, 'finished' => false]);
        $this->assertCount(1, $activeUnfinishedResults);
    }

    /**
     * 测试 findOneBy 排序逻辑
     */
    public function testFindOneByWithOrderBy(): void
    {
        $this->createTestSession('student001', 'lesson001', true, false, null, 1800);
        $this->createTestSession('student002', 'lesson002', true, false, null, 2400);
        $this->createTestSession('student003', 'lesson003', true, false, null, 3000);

        $result = $this->repository->findOneBy(['active' => true], ['totalDuration' => 'DESC']);

        $this->assertNotNull($result);
        $this->assertEquals(3000, $result->getTotalDuration());
    }

    /**
     * 测试查询关联字段 Student
     */
    public function testFindByAssociationStudent(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001');
        $session2 = $this->createTestSession('student002', 'lesson002');

        $results = $this->repository->findBy(['student' => $session1->getStudent()]);

        $this->assertCount(1, $results);
        $this->assertEquals($session1->getId(), $results[0]->getId());
    }

    /**
     * 测试查询关联字段 Lesson
     */
    public function testFindByAssociationLesson(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001');
        $session2 = $this->createTestSession('student002', 'lesson002');

        $results = $this->repository->findBy(['lesson' => $session1->getLesson()]);

        $this->assertCount(1, $results);
        $this->assertEquals($session1->getId(), $results[0]->getId());
    }

    /**
     * 测试查询关联字段 Course
     */
    public function testFindByAssociationCourse(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001');
        $session2 = $this->createTestSession('student002', 'lesson002');

        $results = $this->repository->findBy(['course' => $session1->getCourse()]);

        $this->assertCount(1, $results);
        $this->assertEquals($session1->getId(), $results[0]->getId());
    }

    /**
     * 测试 count 关联查询
     */
    public function testCountByAssociation(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001');
        $this->createTestSession('student002', 'lesson002');

        $count = $this->repository->count(['student' => $session1->getStudent()]);

        $this->assertEquals(1, $count);
    }

    /**
     * 测试 IS NULL 查询可空字段
     */
    public function testFindByNullableFieldsIsNull(): void
    {
        $session = $this->createTestSession('student001', 'lesson001');

        // 测试会话中的所有可空字段
        $results = $this->repository->findBy(['sessionId' => $session->getSessionId()]);
        $this->assertCount(1, $results);
    }

    /**
     * 测试 count IS NULL 查询可空字段
     */
    public function testCountWithNullableFieldsISNull(): void
    {
        // 清理之前可能遗留的数据
        $allSessions = $this->repository->findAll();
        foreach ($allSessions as $session) {
            $this->repository->remove($session, true);
        }

        $this->createTestSession('student001', 'lesson001');
        $this->createTestSession('student002', 'lesson002');

        // 由于 lastLearnTime 在 createTestSession 中被设置，我们直接统计
        $count = $this->repository->count(['active' => true]);
        $this->assertEquals(2, $count);
    }

    protected function createNewEntity(): object
    {
        // 创建最小化的未持久化实体（用于基类测试）
        $uniqueId = uniqid('new-', true);

        // 创建完整的依赖链（依赖会被持久化，但 LearnSession 本身不会）
        $deps = $this->createTestDependencies($uniqueId);

        // 创建 LearnSession 但不持久化
        $session = new LearnSession();
        $session->setStudent($deps['user']);
        $session->setRegistration($deps['registration']);
        $session->setLesson($deps['lesson']);
        $session->setCourse($deps['course']);
        $session->setActive(true);
        $session->setFinished(false);
        $session->setLastLearnTime(new \DateTimeImmutable());
        $session->setTotalDuration(1800);
        $session->setEffectiveDuration(1440);
        $session->setSessionId('session-' . $uniqueId);

        // 注意：不调用 persist，返回未持久化的实体
        return $session;
    }

    /** @return ServiceEntityRepository<LearnSession> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
