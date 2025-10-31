<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;

/**
 * LearnBehaviorRepository 集成测试
 *
 * @template TEntity of LearnBehavior
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnBehaviorRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnBehaviorRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnBehaviorRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnBehavior::class);
        $this->assertInstanceOf(LearnBehaviorRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 创建测试行为记录
     */
    private function createTestBehavior(
        string $sessionId = 'session001',
        BehaviorType $behaviorType = BehaviorType::PLAY,
        bool $isSuspicious = false,
        string $userId = 'user001',
        string $deviceFingerprint = 'device001',
        ?\DateTimeInterface $createTime = null,
        bool $flush = true,
    ): LearnBehavior {
        $behavior = new LearnBehavior();

        // Create a real LearnSession for testing
        $session = $this->createTestSession($sessionId);
        $behavior->setSession($session);
        $behavior->setUserId($userId);
        $behavior->setBehaviorType($behaviorType);
        $behavior->setIsSuspicious($isSuspicious);
        $behavior->setDeviceFingerprint($deviceFingerprint);
        $behavior->setMetadata(['test' => 'data']);

        // 如果指定了createTime，添加微小延迟确保时间差异（用于排序测试）
        if (null !== $createTime) {
            // 暂时等待以确保时间戳差异，避免使用反射API
            usleep(1000); // 1ms延迟
        }

        self::getEntityManager()->persist($behavior);
        if ($flush) {
            self::getEntityManager()->flush();
        }

        return $behavior;
    }

    /**
     * 创建测试会话
     */
    private function createTestSession(string $sessionId): LearnSession
    {
        $session = new LearnSession();
        $session->setStudent($this->createNormalUser('test@example.com'));

        // Create mock registration
        $registration = $this->createMock(Registration::class);
        $session->setRegistration($registration);

        // Create mock lesson
        $lesson = $this->createMock(Lesson::class);
        $session->setLesson($lesson);

        // 设置会话ID用于标识，避免使用反射API
        $session->setSessionId($sessionId);

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        return $session;
    }

    /**
     * 测试根据会话查找可疑行为
     */
    public function testFindSuspiciousBySessionReturnsOnlySuspiciousBehaviors(): void
    {
        $this->createTestBehavior('session001', BehaviorType::RAPID_SEEK, true);
        $this->createTestBehavior('session001', BehaviorType::PLAY, false);
        $this->createTestBehavior('session001', BehaviorType::MULTIPLE_TAB, true);
        $this->createTestBehavior('session002', BehaviorType::FAST_FORWARD, true);

        $results = $this->repository->findSuspiciousBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertTrue($behavior->getIsSuspicious());
            $this->assertEquals('session001', $behavior->getSession()->getSessionId());
        }
    }

    /**
     * 测试会话行为类型统计
     */
    public function testGetBehaviorStatsBySessionReturnsCorrectStats(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PAUSE);
        $this->createTestBehavior('session001', BehaviorType::SEEK);
        $this->createTestBehavior('session002', BehaviorType::PLAY);

        $stats = $this->repository->getBehaviorStatsBySession('session001');

        $this->assertCount(3, $stats);

        $typeStats = [];
        foreach ($stats as $stat) {
            if (is_array($stat) && isset($stat['behaviorType']) && is_object($stat['behaviorType']) && property_exists($stat['behaviorType'], 'value')) {
                $typeStats[$stat['behaviorType']->value] = $stat['count'];
            }
        }

        $this->assertEquals(2, $typeStats[BehaviorType::PLAY->value]);
        $this->assertEquals(1, $typeStats[BehaviorType::PAUSE->value]);
        $this->assertEquals(1, $typeStats[BehaviorType::SEEK->value]);
    }

    /**
     * 测试按时间范围查找行为
     */
    public function testFindByTimeRangeReturnsCorrectRecords(): void
    {
        $startTime = new \DateTimeImmutable('2024-01-15 10:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 12:00:00');

        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 09:00:00'));
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 10:30:00'));
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 11:30:00'));
        $this->createTestBehavior('session004', BehaviorType::STOP, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15 13:00:00'));

        $results = $this->repository->findByTimeRange($startTime, $endTime);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertGreaterThanOrEqual($startTime, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endTime, $behavior->getCreateTime());
        }
    }

    /**
     * 测试根据设备指纹查找行为
     */
    public function testFindByDeviceFingerprintReturnsCorrectRecords(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001');
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user002', 'device001');
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user003', 'device002');

        $results = $this->repository->findByDeviceFingerprint('device001', 10);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('device001', $behavior->getDeviceFingerprint());
        }
    }

    /**
     * 测试根据日期范围查找可疑行为
     */
    public function testFindSuspiciousByDateRangeReturnsOnlySuspiciousBehaviors(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->createTestBehavior('session001', BehaviorType::RAPID_SEEK, true, 'user001', 'device001', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session002', BehaviorType::PLAY, false, 'user002', 'device002', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session003', BehaviorType::FAST_FORWARD, true, 'user003', 'device003', new \DateTimeImmutable('2024-01-20'));
        $this->createTestBehavior('session004', BehaviorType::MULTIPLE_TAB, true, 'user004', 'device004', new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findSuspiciousByDateRange($startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertTrue($behavior->getIsSuspicious());
            $this->assertGreaterThanOrEqual($startDate, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $behavior->getCreateTime());
        }
    }

    /**
     * 测试查找会话最近行为
     */
    public function testFindRecentBySessionReturnsLimitedRecords(): void
    {
        // 创建15条记录，使用批量操作和明确的时间戳
        $behaviors = [];
        $baseTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        for ($i = 1; $i <= 15; ++$i) {
            $createTime = $baseTime->add(new \DateInterval('PT' . $i . 'S'));
            $behaviors[] = $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', $createTime, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findRecentBySession('session001', 10);

        $this->assertCount(10, $results);
        // 验证按时间降序排列
        $previousTime = null;
        foreach ($results as $behavior) {
            if (null !== $previousTime) {
                $this->assertLessThanOrEqual($previousTime, $behavior->getCreateTime());
            }
            $previousTime = $behavior->getCreateTime();
        }
    }

    /**
     * 测试根据用户和日期范围查找行为
     */
    public function testFindByUserAndDateRangeReturnsCorrectRecords(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session002', BehaviorType::PAUSE, false, 'user001', 'device001', new \DateTimeImmutable('2024-01-20'));
        $this->createTestBehavior('session003', BehaviorType::SEEK, false, 'user002', 'device002', new \DateTimeImmutable('2024-01-15'));
        $this->createTestBehavior('session004', BehaviorType::STOP, false, 'user001', 'device001', new \DateTimeImmutable('2024-02-01'));

        $results = $this->repository->findByUserAndDateRange('user001', $startDate, $endDate);

        $this->assertCount(2, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('user001', $behavior->getUserId());
            $this->assertGreaterThanOrEqual($startDate, $behavior->getCreateTime());
            $this->assertLessThanOrEqual($endDate, $behavior->getCreateTime());
        }
    }

    /**
     * 测试根据会话查找所有行为
     */
    public function testFindBySessionReturnsAllSessionBehaviors(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);
        $this->createTestBehavior('session001', BehaviorType::PAUSE);
        $this->createTestBehavior('session001', BehaviorType::SEEK);
        $this->createTestBehavior('session002', BehaviorType::STOP);

        $results = $this->repository->findBySession('session001');

        $this->assertCount(3, $results);
        foreach ($results as $behavior) {
            $this->assertEquals('session001', $behavior->getSession()->getSessionId());
        }
    }

    /**
     * 测试空结果情况
     */
    public function testFindBySessionWithNonExistentSessionReturnsEmptyArray(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY);

        $results = $this->repository->findBySession('non-existent-session');

        $this->assertEmpty($results);
    }

    /**
     * 测试设备指纹限制查询
     */
    public function testFindByDeviceFingerprintRespectsLimit(): void
    {
        // 创建20条记录
        for ($i = 1; $i <= 20; ++$i) {
            $this->createTestBehavior('session' . $i, BehaviorType::PLAY, false, 'user001', 'device001');
        }

        $results = $this->repository->findByDeviceFingerprint('device001', 5);

        $this->assertCount(5, $results);
    }

    /**
     * 测试行为类型统计排序
     */
    public function testGetBehaviorStatsBySessionOrderedByCountDesc(): void
    {
        // 创建不同数量的行为类型
        for ($i = 0; $i < 5; ++$i) {
            $this->createTestBehavior('session001', BehaviorType::PLAY);
        }
        for ($i = 0; $i < 3; ++$i) {
            $this->createTestBehavior('session001', BehaviorType::PAUSE);
        }
        for ($i = 0; $i < 7; ++$i) {
            $this->createTestBehavior('session001', BehaviorType::SEEK);
        }

        $stats = $this->repository->getBehaviorStatsBySession('session001');

        $this->assertCount(3, $stats);
        // 验证按数量降序排列
        $this->assertEquals(BehaviorType::SEEK, $stats[0]['behaviorType']);
        $this->assertEquals(7, $stats[0]['count']);
        $this->assertEquals(BehaviorType::PLAY, $stats[1]['behaviorType']);
        $this->assertEquals(5, $stats[1]['count']);
        $this->assertEquals(BehaviorType::PAUSE, $stats[2]['behaviorType']);
        $this->assertEquals(3, $stats[2]['count']);
    }

    /**
     * 测试根据用户ID和课程ID查找学习行为
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
     * 测试按日期范围和过滤条件查找行为
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
     * 测试按日期范围和过滤条件查找行为（带课程ID）
     */
    public function testFindByDateRangeAndFiltersWithCourseId(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $filters = ['courseId' => 'course_' . uniqid()];

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate, $filters);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试按日期范围和过滤条件查找行为（带用户ID）
     */
    public function testFindByDateRangeAndFiltersWithUserId(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $filters = ['userId' => 'user_' . uniqid()];

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate, $filters);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== 基础 CRUD 操作测试 =====

    public function testFindAllShouldReturnArrayOfEntities(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnBehavior::class, $entity);
        }
    }

    public function testFindOneByWithNonExistingCriteriaShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['id' => 'nonexistent_id_' . uniqid()]);
        $this->assertNull($result);
    }

    public function testFindWithValidIdShouldReturnEntity(): void
    {
        $behavior = $this->createTestBehavior();
        $result = $this->repository->find($behavior->getId());
        $this->assertInstanceOf(LearnBehavior::class, $result);
        $this->assertEquals($behavior->getId(), $result->getId());
    }

    public function testFindWithInvalidIdShouldReturnNull(): void
    {
        $result = $this->repository->find('nonexistent_id_' . uniqid());
        $this->assertNull($result);
    }

    public function testCountShouldReturnInteger(): void
    {
        $initialCount = $this->repository->count([]);
        $this->assertIsInt($initialCount);

        $this->createTestBehavior();
        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testCountWithCriteriaShouldReturnCorrectNumber(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY, true);
        $this->createTestBehavior('session001', BehaviorType::PAUSE, false);

        $suspiciousCount = $this->repository->count(['isSuspicious' => true]);
        $this->assertEquals(1, $suspiciousCount);

        $normalCount = $this->repository->count(['isSuspicious' => false]);
        $this->assertGreaterThanOrEqual(1, $normalCount);
    }

    // ===== 保存和删除操作测试 =====

    public function testSaveShouldPersistEntity(): void
    {
        $behavior = new LearnBehavior();
        $session = $this->createTestSession('test_session');
        $behavior->setSession($session);
        $behavior->setBehaviorType(BehaviorType::PLAY);
        $behavior->setUserId('test_user');

        $initialCount = $this->repository->count([]);
        $this->repository->save($behavior);
        $newCount = $this->repository->count([]);

        $this->assertEquals($initialCount + 1, $newCount);
        $this->assertNotNull($behavior->getId());
    }

    public function testSaveWithoutFlushShouldNotPersistImmediately(): void
    {
        $behavior = new LearnBehavior();
        $session = $this->createTestSession('test_session');
        $behavior->setSession($session);
        $behavior->setBehaviorType(BehaviorType::PLAY);
        $behavior->setUserId('test_user');

        $initialCount = $this->repository->count([]);
        $this->repository->save($behavior, false);

        // 在未 flush 前，实体已被 persist 但可能还未分配 ID（取决于 ID 生成策略）
        self::getEntityManager()->flush();
        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        $behavior = $this->createTestBehavior();
        $initialCount = $this->repository->count([]);

        $this->repository->remove($behavior);
        $newCount = $this->repository->count([]);

        $this->assertEquals($initialCount - 1, $newCount);
        $this->assertNull($this->repository->find($behavior->getId()));
    }

    public function testRemoveWithoutFlushShouldNotDeleteImmediately(): void
    {
        $behavior = $this->createTestBehavior();
        $behaviorId = $behavior->getId();
        $initialCount = $this->repository->count([]);

        $this->repository->remove($behavior, false);

        // 在未 flush 前，实体仍然可以找到
        $this->assertInstanceOf(LearnBehavior::class, $this->repository->find($behaviorId));

        self::getEntityManager()->flush();
        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount - 1, $newCount);
    }

    // ===== 可空字段查询测试 =====

    public function testFindByVideoTimestampShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setVideoTimestamp('120.5000');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior();
        $behavior2->setVideoTimestamp('240.7500');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // 没有 videoTimestamp

        $results = $this->repository->findBy(['videoTimestamp' => '120.5000']);
        $this->assertCount(1, $results);
        $this->assertEquals('120.5000', $results[0]->getVideoTimestamp());
    }

    public function testFindByIpAddressShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setIpAddress('192.168.1.100');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior();
        $behavior2->setIpAddress('10.0.0.1');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // 没有 IP 地址

        $results = $this->repository->findBy(['ipAddress' => '192.168.1.100']);
        $this->assertCount(1, $results);
        $this->assertEquals('192.168.1.100', $results[0]->getIpAddress());
    }

    public function testFindByUserAgentShouldReturnMatchingRecords(): void
    {
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $behavior1 = $this->createTestBehavior();
        $behavior1->setUserAgent($userAgent);
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // 没有 User-Agent

        $results = $this->repository->findBy(['userAgent' => $userAgent]);
        $this->assertCount(1, $results);
        $this->assertEquals($userAgent, $results[0]->getUserAgent());
    }

    public function testFindBySuspiciousReasonShouldReturnMatchingRecords(): void
    {
        $reason = '检测到异常快进行为';

        $behavior1 = $this->createTestBehavior('session001', BehaviorType::FAST_FORWARD, true);
        $behavior1->setSuspiciousReason($reason);
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // 没有可疑原因

        $results = $this->repository->findBy(['suspiciousReason' => $reason]);
        $this->assertCount(1, $results);
        $this->assertEquals($reason, $results[0]->getSuspiciousReason());
    }

    public function testFindByBehaviorDataShouldReturnMatchingRecords(): void
    {
        $behaviorData = ['speed' => 2.0, 'position' => 300];

        $behavior1 = $this->createTestBehavior();
        $behavior1->setBehaviorData($behaviorData);
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // 使用默认的 metadata

        // 由于JSON字段的查询复杂性，这里主要测试基本功能
        $allBehaviors = $this->repository->findAll();
        $found = false;
        foreach ($allBehaviors as $behavior) {
            if ($behavior->getBehaviorData() === $behaviorData) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ===== 边界条件和异常情况测试 =====

    public function testFindBySessionWithEmptySessionIdShouldReturnEmptyArray(): void
    {
        $results = $this->repository->findBySession('');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindSuspiciousBySessionWithNoSuspiciousBehaviorsShouldReturnEmptyArray(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY, false);
        $this->createTestBehavior('session001', BehaviorType::PAUSE, false);

        $results = $this->repository->findSuspiciousBySession('session001');
        $this->assertEmpty($results);
    }

    public function testGetBehaviorStatsBySessionWithEmptySessionShouldReturnEmptyArray(): void
    {
        $stats = $this->repository->getBehaviorStatsBySession('nonexistent_session');
        $this->assertEmpty($stats);
    }

    public function testFindByTimeRangeWithNoRecordsInRangeShouldReturnEmptyArray(): void
    {
        $futureStart = new \DateTimeImmutable('+1 year');
        $futureEnd = new \DateTimeImmutable('+2 years');

        $results = $this->repository->findByTimeRange($futureStart, $futureEnd);
        $this->assertEmpty($results);
    }

    public function testFindByDeviceFingerprintWithZeroLimitShouldReturnEmptyArray(): void
    {
        $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001');

        $results = $this->repository->findByDeviceFingerprint('device001', 0);
        $this->assertEmpty($results);
    }

    public function testFindOneByWithOrderByShouldReturnCorrectEntity(): void
    {
        $baseTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', $baseTime, false);
        $behavior2 = $this->createTestBehavior('session002', BehaviorType::PLAY, false, 'user001', 'device001', $baseTime->add(new \DateInterval('PT1S')), false);
        self::getEntityManager()->flush();

        $resultAsc = $this->repository->findOneBy(['userId' => 'user001'], ['createTime' => 'ASC']);
        $this->assertNotNull($resultAsc);
        $this->assertEquals($behavior1->getId(), $resultAsc->getId());

        $resultDesc = $this->repository->findOneBy(['userId' => 'user001'], ['createTime' => 'DESC']);
        $this->assertNotNull($resultDesc);
        $this->assertEquals($behavior2->getId(), $resultDesc->getId());
    }

    // ===== 关联查询测试 =====

    public function testCountBySessionAssociationShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior('session001');
        $behavior2 = $this->createTestBehavior('session001');
        $behavior3 = $this->createTestBehavior('session002');

        $mockSession = $behavior1->getSession();
        $count = $this->repository->count(['session' => $mockSession]);
        $this->assertEquals(2, $count);
    }

    public function testFindBySessionAssociationShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior('session001');
        $behavior2 = $this->createTestBehavior('session001');
        $behavior3 = $this->createTestBehavior('session002');

        $mockSession = $behavior1->getSession();
        $results = $this->repository->findBy(['session' => $mockSession]);
        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals($mockSession->getSessionId(), $result->getSession()->getSessionId());
        }
    }

    public function testFindOneBySessionAssociationShouldReturnEntity(): void
    {
        $behavior = $this->createTestBehavior('session001');
        $mockSession = $behavior->getSession();

        $result = $this->repository->findOneBy(['session' => $mockSession]);
        $this->assertInstanceOf(LearnBehavior::class, $result);
        $this->assertEquals($mockSession->getSessionId(), $result->getSession()->getSessionId());
    }

    // ===== IS NULL 查询测试 =====

    public function testFindByVideoTimestampIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setVideoTimestamp('120.0000');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior(); // videoTimestamp 为 null

        $results = $this->repository->findBy(['videoTimestamp' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getVideoTimestamp());
        }
    }

    public function testFindByIpAddressIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setIpAddress('192.168.1.1');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior(); // ipAddress 为 null

        $results = $this->repository->findBy(['ipAddress' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getIpAddress());
        }
    }

    public function testCountWithVideoTimestampIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setVideoTimestamp('120.0000');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // videoTimestamp 为 null
        $this->createTestBehavior(); // videoTimestamp 为 null

        $nullCount = $this->repository->count(['videoTimestamp' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testCountWithIpAddressIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setIpAddress('192.168.1.1');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // ipAddress 为 null
        $this->createTestBehavior(); // ipAddress 为 null

        $nullCount = $this->repository->count(['ipAddress' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindByUserAgentIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setUserAgent('Mozilla/5.0 (Test)');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior(); // userAgent 为 null

        $results = $this->repository->findBy(['userAgent' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getUserAgent());
        }
    }

    public function testCountWithUserAgentIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setUserAgent('Mozilla/5.0 (Test)');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // userAgent 为 null
        $this->createTestBehavior(); // userAgent 为 null

        $nullCount = $this->repository->count(['userAgent' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindByDeviceFingerprintIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setDeviceFingerprint('device123');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior(); // deviceFingerprint 为 null

        $results = $this->repository->findBy(['deviceFingerprint' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getDeviceFingerprint());
        }
    }

    public function testCountWithDeviceFingerprintIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setDeviceFingerprint('device123');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // deviceFingerprint 为 null
        $this->createTestBehavior(); // deviceFingerprint 为 null

        $nullCount = $this->repository->count(['deviceFingerprint' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindBySuspiciousReasonIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::FAST_FORWARD, true);
        $behavior1->setSuspiciousReason('有可疑行为');
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior(); // suspiciousReason 为 null

        $results = $this->repository->findBy(['suspiciousReason' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getSuspiciousReason());
        }
    }

    public function testCountWithSuspiciousReasonIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::FAST_FORWARD, true);
        $behavior1->setSuspiciousReason('有可疑行为');
        self::getEntityManager()->flush();

        $this->createTestBehavior(); // suspiciousReason 为 null
        $this->createTestBehavior(); // suspiciousReason 为 null

        $nullCount = $this->repository->count(['suspiciousReason' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindByBehaviorDataIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setBehaviorData(['key' => 'value']);
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior();
        $behavior2->setBehaviorData(null);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['behaviorData' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getBehaviorData());
        }
    }

    public function testCountWithBehaviorDataIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setBehaviorData(['key' => 'value']);
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior();
        $behavior2->setBehaviorData(null);
        self::getEntityManager()->flush();

        $behavior3 = $this->createTestBehavior();
        $behavior3->setBehaviorData(null);
        self::getEntityManager()->flush();

        $nullCount = $this->repository->count(['behaviorData' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindByMetadataIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior();
        // 默认会设置 metadata，所以先清空
        $behavior1->setMetadata(null);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['metadata' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getMetadata());
        }
    }

    public function testCountWithMetadataIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior();
        $behavior1->setMetadata(null);
        self::getEntityManager()->flush();

        $behavior2 = $this->createTestBehavior();
        $behavior2->setMetadata(null);
        self::getEntityManager()->flush();

        $nullCount = $this->repository->count(['metadata' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    public function testFindByUserIdIsNullShouldReturnMatchingRecords(): void
    {
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001');

        $behavior2 = $this->createTestBehavior();
        $behavior2->setUserId(null);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['userId' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getUserId());
        }
    }

    public function testCountWithUserIdIsNullShouldReturnCorrectNumber(): void
    {
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001');

        $behavior2 = $this->createTestBehavior();
        $behavior2->setUserId(null);
        self::getEntityManager()->flush();

        $behavior3 = $this->createTestBehavior();
        $behavior3->setUserId(null);
        self::getEntityManager()->flush();

        $nullCount = $this->repository->count(['userId' => null]);
        $this->assertGreaterThanOrEqual(2, $nullCount);
    }

    // 更具体的 findOneBy 排序测试
    public function testFindOneByOrderByShouldReturnFirstEntityBySort(): void
    {
        $baseTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $behavior1 = $this->createTestBehavior('session001', BehaviorType::PLAY, false, 'user001', 'device001', $baseTime, false);
        $behavior2 = $this->createTestBehavior('session002', BehaviorType::PLAY, false, 'user001', 'device001', $baseTime->add(new \DateInterval('PT1S')), false);
        $behavior3 = $this->createTestBehavior('session003', BehaviorType::PLAY, false, 'user001', 'device001', $baseTime->add(new \DateInterval('PT2S')), false);
        self::getEntityManager()->flush();

        // 测试按 createTime ASC 排序
        $resultAsc = $this->repository->findOneBy(['userId' => 'user001'], ['createTime' => 'ASC']);
        $this->assertInstanceOf(LearnBehavior::class, $resultAsc);
        $this->assertEquals($behavior1->getId(), $resultAsc->getId());

        // 测试按 createTime DESC 排序
        $resultDesc = $this->repository->findOneBy(['userId' => 'user001'], ['createTime' => 'DESC']);
        $this->assertInstanceOf(LearnBehavior::class, $resultDesc);
        $this->assertEquals($behavior3->getId(), $resultDesc->getId());
    }

    protected function createNewEntity(): object
    {
        return $this->createTestBehavior();
    }

    /** @return ServiceEntityRepository<LearnBehavior> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
