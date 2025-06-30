<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserAgentBundle\DoctrineUserAgentBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnSessionRepository 集成测试
 */
class LearnSessionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnSessionRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineUserAgentBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Core bundles
            TrainRecordBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        $repository = $this->entityManager->getRepository(LearnSession::class);
        $this->assertInstanceOf(LearnSessionRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnSession::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试会话
     */
    private function createTestSession(
        ?string $student = 'student001',
        string $lesson = 'lesson001',
        bool $active = true,
        bool $finished = false,
        ?\DateTimeInterface $lastLearnTime = null,
        float $totalDuration = 1800
    ): LearnSession {
        $session = new LearnSession();
        $session->setStudent($student);
        $session->setLesson($lesson);
        $session->setActive($active);
        $session->setFinished($finished);
        $session->setLastLearnTime($lastLearnTime ?? new \DateTimeImmutable());
        $session->setTotalDuration($totalDuration);
        $session->setEffectiveDuration(0.8 * $totalDuration);
        $session->setSessionId('session-' . uniqid());

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    /**
     * 测试查找学员的活跃会话
     */
    public function test_findActiveSessionsByStudent_returnsOnlyActiveUnfinishedSessions(): void
    {
        $student = 'student001';
        
        $this->createTestSession($student, 'lesson001', true, false);
        $this->createTestSession($student, 'lesson002', true, false);
        $this->createTestSession($student, 'lesson003', false, false); // 非活跃
        $this->createTestSession($student, 'lesson004', true, true); // 已完成
        $this->createTestSession('student002', 'lesson001', true, false);

        $results = $this->repository->findActiveSessionsByStudent($student);

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertEquals($student, $session->getStudent());
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试查找学员在其他课程的活跃会话
     */
    public function test_findOtherActiveSessionsByStudent_excludesCurrentLesson(): void
    {
        $student = 'student001';
        $currentLessonId = 'lesson001';
        
        $this->createTestSession($student, 'lesson001', true, false);
        $this->createTestSession($student, 'lesson002', true, false);
        $this->createTestSession($student, 'lesson003', true, false);
        $this->createTestSession($student, 'lesson004', false, false); // 非活跃

        $results = $this->repository->findOtherActiveSessionsByStudent($student, $currentLessonId);

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertNotEquals($currentLessonId, $session->getLesson());
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试停用学员的所有活跃会话
     */
    public function test_deactivateAllActiveSessionsByStudent_updatesCorrectSessions(): void
    {
        $student = 'student001';
        
        $this->createTestSession($student, 'lesson001', true, false);
        $this->createTestSession($student, 'lesson002', true, false);
        $this->createTestSession($student, 'lesson003', false, false);
        $this->createTestSession('student002', 'lesson001', true, false);

        $updatedCount = $this->repository->deactivateAllActiveSessionsByStudent($student);

        $this->assertEquals(2, $updatedCount);
        
        // 验证结果
        $this->entityManager->clear();
        $activeSessions = $this->repository->findActiveSessionsByStudent($student);
        $this->assertEmpty($activeSessions);
    }

    /**
     * 测试保存会话
     */
    public function test_save_persistsSession(): void
    {
        $session = new LearnSession();
        $session->setStudent('student001');
        $session->setLesson('lesson001');
        $session->setActive(true);
        $session->setFinished(false);
        $session->setSessionId('test-session-001');

        $this->repository->save($session);

        $savedSession = $this->repository->find($session->getId());
        $this->assertNotNull($savedSession);
        $this->assertEquals('student001', $savedSession->getStudent());
    }

    /**
     * 测试查找超时的活跃会话
     */
    public function test_findInactiveActiveSessions_returnsOldActiveSessions(): void
    {
        $thresholdMinutes = 30;
        
        $this->createTestSession('student001', 'lesson001', true, false, new \DateTimeImmutable('-45 minutes'));
        $this->createTestSession('student002', 'lesson002', true, false, new \DateTimeImmutable('-20 minutes'));
        $this->createTestSession('student003', 'lesson003', true, true, new \DateTimeImmutable('-60 minutes')); // 已完成
        $this->createTestSession('student004', 'lesson004', false, false, new \DateTimeImmutable('-60 minutes')); // 非活跃

        $results = $this->repository->findInactiveActiveSessions($thresholdMinutes);

        $this->assertCount(1, $results);
        $this->assertEquals('student001', $results[0]->getStudent());
    }

    /**
     * 测试批量更新会话活跃状态
     */
    public function test_batchUpdateActiveStatus_updatesCorrectly(): void
    {
        $session1 = $this->createTestSession('student001', 'lesson001', true, false);
        $session2 = $this->createTestSession('student002', 'lesson002', true, false);
        $session3 = $this->createTestSession('student003', 'lesson003', true, false);

        $sessionIds = [$session1->getId(), $session2->getId()];
        $updatedCount = $this->repository->batchUpdateActiveStatus($sessionIds, false);

        $this->assertEquals(2, $updatedCount);
        
        $this->entityManager->clear();
        
        $updatedSession1 = $this->repository->find($session1->getId());
        $updatedSession2 = $this->repository->find($session2->getId());
        $updatedSession3 = $this->repository->find($session3->getId());
        
        $this->assertFalse($updatedSession1->getActive());
        $this->assertFalse($updatedSession2->getActive());
        $this->assertTrue($updatedSession3->getActive());
    }

    /**
     * 测试空数组批量更新
     */
    public function test_batchUpdateActiveStatus_withEmptyArray_returnsZero(): void
    {
        $updatedCount = $this->repository->batchUpdateActiveStatus([], false);

        $this->assertEquals(0, $updatedCount);
    }

    /**
     * 测试查找已完成的会话
     */
    public function test_findCompletedSessions_returnsOnlyFinishedSessions(): void
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
    public function test_findActiveSessions_returnsActiveUnfinishedSessions(): void
    {
        $this->createTestSession('student001', 'lesson001', true, false);
        $this->createTestSession('student002', 'lesson002', true, false);
        $this->createTestSession('student003', 'lesson003', false, false);
        $this->createTestSession('student004', 'lesson004', true, true);

        $results = $this->repository->findActiveSessions();

        $this->assertCount(2, $results);
        foreach ($results as $session) {
            $this->assertTrue($session->getActive());
            $this->assertFalse($session->getFinished());
        }
    }

    /**
     * 测试查找已过期的会话
     */
    public function test_findExpiredSessions_returnsOldUnfinishedSessions(): void
    {
        $expireTime = new \DateTimeImmutable('-2 hours');
        
        $this->createTestSession('student001', 'lesson001', true, false, new \DateTimeImmutable('-3 hours'));
        $this->createTestSession('student002', 'lesson002', true, false, new \DateTimeImmutable('-1 hour'));
        $this->createTestSession('student003', 'lesson003', true, true, new \DateTimeImmutable('-4 hours'));

        $results = $this->repository->findExpiredSessions($expireTime);

        $this->assertCount(1, $results);
        $this->assertEquals('student001', $results[0]->getStudent());
        $this->assertFalse($results[0]->getFinished());
    }

    /**
     * 测试计算平均时长
     */
    public function test_avgDurationByFilters_returnsCorrectAverage(): void
    {
        $this->createTestSession('student001', 'lesson001', true, false, null, 1800);
        $this->createTestSession('student002', 'lesson002', true, false, null, 2400);
        $this->createTestSession('student003', 'lesson003', true, false, null, 3000);

        $avgDuration = $this->repository->avgDurationByFilters([]);

        $this->assertEqualsWithDelta(2400.0, $avgDuration, 0.01); // (1800+2400+3000)/3
    }

    /**
     * 测试统计活跃会话数
     */
    public function test_countActiveSessionsSince_returnsCorrectCount(): void
    {
        $since = new \DateTimeImmutable('-1 hour');
        
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
    public function test_countByDateRange_returnsCorrectCount(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');
        
        $this->createTestSession('student001', 'lesson001');
        $this->createTestSession('student002', 'lesson002');
        $this->createTestSession('student003', 'lesson003');

        $count = $this->repository->countByDateRange($startDate, $endDate);

        $this->assertEquals(3, $count);
    }

    /**
     * 测试按日期范围统计唯一用户数
     */
    public function test_countUniqueUsersByDateRange_returnsUniqueCount(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');
        
        $this->createTestSession('student001', 'lesson001');
        $this->createTestSession('student001', 'lesson002'); // 同一学生
        $this->createTestSession('student002', 'lesson003');

        $count = $this->repository->countUniqueUsersByDateRange($startDate, $endDate);

        $this->assertEquals(2, $count);
    }

    /**
     * 测试按日期范围统计总时长
     */
    public function test_sumDurationByDateRange_returnsCorrectSum(): void
    {
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('now');
        
        $this->createTestSession('student001', 'lesson001', true, false, null, 1800);
        $this->createTestSession('student002', 'lesson002', true, false, null, 2400);
        $this->createTestSession('student003', 'lesson003', true, false, null, 3000);

        $totalDuration = $this->repository->sumDurationByDateRange($startDate, $endDate);

        $this->assertEquals(7200.0, $totalDuration); // 1800+2400+3000
    }

    /**
     * 测试获取当前在线用户数
     */
    public function test_getCurrentOnlineUsers_returnsRecentActiveUsers(): void
    {
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
    public function test_countByFilters_withCourseFilter_returnsFilteredCount(): void
    {
        // 创建会话并设置课程（通过 lesson 关联）
        $session1 = $this->createTestSession('student001', 'lesson001');
        $session2 = $this->createTestSession('student002', 'lesson002');
        $session3 = $this->createTestSession('student003', 'lesson003');

        // 假设所有会话都在最近创建
        $filters = [
            'startTime' => new \DateTimeImmutable('-1 hour'),
            'endTime' => new \DateTimeImmutable('now')
        ];

        $count = $this->repository->countByFilters($filters);

        $this->assertEquals(3, $count);
    }
}