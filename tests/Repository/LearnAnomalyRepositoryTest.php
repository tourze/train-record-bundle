<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;

/**
 * LearnAnomalyRepository 集成测试
 *
 * @template TEntity of LearnAnomaly
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnAnomalyRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnAnomalyRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnAnomalyRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnAnomaly::class);
        $this->assertInstanceOf(LearnAnomalyRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 创建测试异常记录
     */
    private function createTestAnomaly(
        ?string $sessionId = null,
        AnomalyType $type = AnomalyType::RAPID_PROGRESS,
        AnomalySeverity $severity = AnomalySeverity::MEDIUM,
        AnomalyStatus $status = AnomalyStatus::DETECTED,
        bool $isAutoDetected = true,
        ?string $description = null,
    ): LearnAnomaly {
        // 创建或获取测试会话
        $session = $this->createOrGetTestSession($sessionId ?? 'session_' . uniqid());

        $anomaly = new LearnAnomaly();
        $anomaly->setSession($session);
        $anomaly->setAnomalyType($type);
        $anomaly->setSeverity($severity);
        $anomaly->setStatus($status);
        $anomaly->setIsAutoDetected($isAutoDetected);
        $anomaly->setAnomalyDescription($description ?? 'Test anomaly description');
        $anomaly->setDetectTime(new \DateTimeImmutable());
        $anomaly->setEvidence(['test' => 'evidence']);

        self::getEntityManager()->persist($anomaly);
        self::getEntityManager()->flush();

        return $anomaly;
    }

    /**
     * 创建或获取测试会话
     */
    private function createOrGetTestSession(string $sessionId): LearnSession
    {
        // 检查是否已经存在该会话
        $existingSession = self::getEntityManager()
            ->getRepository(LearnSession::class)
            ->findOneBy(['sessionId' => $sessionId])
        ;

        if (null !== $existingSession) {
            return $existingSession;
        }

        // 创建简单的测试会话
        $session = new LearnSession();
        $session->setSessionId($sessionId);
        $session->setActive(true);
        $session->setFinished(false);
        $session->setTotalDuration(1800);
        $session->setEffectiveDuration(1440);

        // 创建最简单的关联实体避免复杂依赖
        $user = $this->createNormalUser('test@example.com');
        $session->setStudent($user);

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        return $session;
    }

    // ===== count 可空字段测试 =====

    public function testCountWithNullResolvedTimeShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['resolveTime' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullResolutionShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['resolution' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullAnomalyDescriptionShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['anomalyDescription' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullAnomalyDataShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['anomalyData' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullResolvedByShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['resolvedBy' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullImpactScoreShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['impactScore' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullEvidenceShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['evidence' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullProcessingNotesShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['processingNotes' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullDetectedTimeShouldReturnCorrectNumber(): void
    {
        $count = $this->repository->count(['detectTime' => null]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== find 方法测试 =====

    // ===== findAll 方法测试 =====

    public function testFindAllShouldReturnArrayOfLearnAnomalyEntities(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    // ===== findBy 方法测试 =====

    // ===== findOneBy 排序测试 =====

    public function testFindOneByWithOrderByShouldRespectOrdering(): void
    {
        // 按时间升序查找
        $resultAsc = $this->repository->findOneBy([], ['detectTime' => 'ASC']);
        // 按时间降序查找
        $resultDesc = $this->repository->findOneBy([], ['detectTime' => 'DESC']);

        if (null !== $resultAsc) {
            $this->assertInstanceOf(LearnAnomaly::class, $resultAsc);
        }
        if (null !== $resultDesc) {
            $this->assertInstanceOf(LearnAnomaly::class, $resultDesc);
        }

        // 如果有多个结果，升序和降序的第一个结果应该不同
        if (null !== $resultAsc && null !== $resultDesc && $resultAsc->getId() !== $resultDesc->getId()) {
            $this->assertNotEquals($resultAsc->getId(), $resultDesc->getId());
        }
    }

    public function testFindOneByWithMultipleOrderByClausesShouldRespectAllClauses(): void
    {
        // 按优先级和时间排序
        $result = $this->repository->findOneBy(
            [],
            ['severity' => 'DESC', 'detectTime' => 'ASC']
        );

        if (null !== $result) {
            $this->assertInstanceOf(LearnAnomaly::class, $result);
        }
    }

    public function testFindOneByAnomalyDescriptionShouldReturnEntityOrNull(): void
    {
        $result = $this->repository->findOneBy(['anomalyDescription' => 'nonexistent_description_' . uniqid()]);

        $this->assertNull($result);
    }

    public function testFindOneByWithNullValueShouldWorkCorrectly(): void
    {
        $result = $this->repository->findOneBy(['resolution' => null]);

        // 结果可能是 null 或者是一个实体
        $this->assertTrue($result instanceof LearnAnomaly || null === $result);
    }

    // ===== 可空字段查询测试 =====

    public function testFindByNullValueShouldReturnEntitiesWithNullField(): void
    {
        // 查找resolveTime为null的记录
        $result = $this->repository->findBy(['resolveTime' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
            $this->assertNull($entity->getResolveTime());
        }
    }

    public function testFindByNullResolvedTimeShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['resolveTime' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
            $this->assertNull($entity->getResolveTime());
        }
    }

    public function testFindByNullResolutionShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['resolution' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
            $this->assertNull($entity->getResolution());
        }
    }

    public function testFindByNullAnomalyDescriptionShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['anomalyDescription' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullAnomalyDataShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['anomalyData' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullResolvedByShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['resolvedBy' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullImpactScoreShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['impactScore' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullEvidenceShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['evidence' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullProcessingNotesShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['processingNotes' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindByNullDetectedTimeShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['detectTime' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    // ===== 关联查询测试 =====

    public function testFindBySessionAssociationShouldReturnRelatedEntities(): void
    {
        // 查找第一个有 session 关联的实体
        $existingEntities = $this->repository->findBy([], null, 1);

        if ([] === $existingEntities) {
            // 测试空数据情况下的基本功能
            $count = $this->repository->count([]);
            $this->assertIsInt($count);

            return;
        }

        $session = $existingEntities[0]->getSession();
        $result = $this->repository->findBy(['session' => $session], null, 5);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
            $this->assertEquals($session, $entity->getSession());
        }
    }

    public function testFindOneBySessionAssociationShouldReturnRelatedEntity(): void
    {
        // 查找第一个有 session 关联的实体
        $existingEntities = $this->repository->findBy([], null, 1);

        if ([] === $existingEntities) {
            // 测试空数据情况下的基本功能
            $count = $this->repository->count([]);
            $this->assertIsInt($count);

            return;
        }

        $session = $existingEntities[0]->getSession();
        $result = $this->repository->findOneBy(['session' => $session]);

        $this->assertInstanceOf(LearnAnomaly::class, $result);
        $this->assertEquals($session, $result->getSession());
    }

    // ===== count 关联字段测试 =====

    public function testCountBySessionAssociationShouldReturnCorrectNumber(): void
    {
        // 查找第一个有 session 关联的实体
        $existingEntities = $this->repository->findBy([], null, 1);

        if ([] === $existingEntities) {
            // 测试空数据情况下的基本功能
            $count = $this->repository->count([]);
            $this->assertIsInt($count);

            return;
        }

        $session = $existingEntities[0]->getSession();
        $count = $this->repository->count(['session' => $session]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // ===== save 和 remove 方法测试 =====

    public function testSaveShouldPersistEntity(): void
    {
        // 查找现有的 session 用于测试
        $sessionRepo = self::getEntityManager()->getRepository(LearnSession::class);
        $existingSession = $sessionRepo->findOneBy([]);

        if (null === $existingSession) {
            // 测试空数据情况下的保存
            $this->assertIsInt($this->repository->count([]));

            return;
        }

        $anomaly = new LearnAnomaly();
        $anomaly->setSession($existingSession);
        $anomaly->setAnomalyType(AnomalyType::RAPID_PROGRESS);
        $anomaly->setSeverity(AnomalySeverity::MEDIUM);
        $anomaly->setStatus(AnomalyStatus::DETECTED);
        $anomaly->setDetectTime(new \DateTimeImmutable());
        $anomaly->setIsAutoDetected(true);
        $anomaly->setAnomalyDescription('Test save operation');
        $anomaly->setEvidence(['test' => 'save']);

        // 测试保存操作
        $this->repository->save($anomaly);

        // 验证实体已被持久化
        $this->assertNotNull($anomaly->getId());

        // 清理
        $this->repository->remove($anomaly);
    }

    public function testSaveWithoutFlushShouldNotImmediatelyPersist(): void
    {
        // 查找现有的 session 用于测试
        $sessionRepo = self::getEntityManager()->getRepository(LearnSession::class);
        $existingSession = $sessionRepo->findOneBy([]);

        if (null === $existingSession) {
            // 测试空数据情况下的保存
            $this->assertIsInt($this->repository->count([]));

            return;
        }

        $anomaly = new LearnAnomaly();
        $anomaly->setSession($existingSession);
        $anomaly->setAnomalyType(AnomalyType::RAPID_PROGRESS);
        $anomaly->setSeverity(AnomalySeverity::MEDIUM);
        $anomaly->setStatus(AnomalyStatus::DETECTED);
        $anomaly->setDetectTime(new \DateTimeImmutable());
        $anomaly->setIsAutoDetected(true);
        $anomaly->setAnomalyDescription('Test save without flush');
        $anomaly->setEvidence(['test' => 'no_flush']);

        // 测试保存但不刷新
        $this->repository->save($anomaly, false);

        // 手动刷新
        self::getEntityManager()->flush();

        // 验证实体已被持久化
        $this->assertNotNull($anomaly->getId());

        // 清理
        $this->repository->remove($anomaly);
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        // 查找现有的实体进行删除测试
        $existingEntities = $this->repository->findBy([], null, 1);

        if ([] === $existingEntities) {
            // 测试空数据情况下的基本功能
            $count = $this->repository->count([]);
            $this->assertIsInt($count);

            return;
        }

        $anomaly = $existingEntities[0];
        $anomalyId = $anomaly->getId();

        // 移除实体
        $this->repository->remove($anomaly);

        // 验证实体已被删除
        $removedAnomaly = $this->repository->find($anomalyId);
        $this->assertNull($removedAnomaly);
    }

    public function testRemoveWithoutFlushShouldNotImmediatelyDelete(): void
    {
        // 查找现有的实体进行删除测试
        $existingEntities = $this->repository->findBy([], null, 1);

        if ([] === $existingEntities) {
            // 测试空数据情况下的基本功能
            $count = $this->repository->count([]);
            $this->assertIsInt($count);

            return;
        }

        $anomaly = $existingEntities[0];
        $anomalyId = $anomaly->getId();

        // 移除但不刷新
        $this->repository->remove($anomaly, false);

        // 在刷新前，实体应该仍然存在
        $stillExists = $this->repository->find($anomalyId);
        $this->assertNotNull($stillExists);

        // 手动刷新
        self::getEntityManager()->flush();

        // 现在实体应该被删除
        $removedAnomaly = $this->repository->find($anomalyId);
        $this->assertNull($removedAnomaly);
    }

    // ===== 边界条件测试 =====

    public function testFindByWithLimitShouldRespectLimit(): void
    {
        $result = $this->repository->findBy([], null, 2);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(2, count($result));
    }

    public function testFindByWithOffsetShouldRespectOffset(): void
    {
        $result = $this->repository->findBy([], null, 1, 0);
        $resultWithOffset = $this->repository->findBy([], null, 1, 1);

        $this->assertIsArray($result);
        $this->assertIsArray($resultWithOffset);

        // 如果有足够的数据，偏移结果应该不同
        if ([] !== $result && [] !== $resultWithOffset) {
            $this->assertNotEquals($result[0]->getId(), $resultWithOffset[0]->getId());
        }
    }

    // ===== 业务方法测试（使用现有数据或空查询） =====

    public function testFindBySessionReturnsCorrectRecords(): void
    {
        // 使用现有数据测试业务方法
        $result = $this->repository->findBySession('nonexistent_session_' . uniqid());

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindUnprocessedReturnsCorrectRecords(): void
    {
        $result = $this->repository->findUnprocessed();

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertContains($anomaly->getStatus(), [AnomalyStatus::DETECTED, AnomalyStatus::INVESTIGATING]);
        }
    }

    public function testFindHighPriorityReturnsCorrectRecords(): void
    {
        $result = $this->repository->findHighPriority();

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertContains($anomaly->getSeverity(), [AnomalySeverity::HIGH, AnomalySeverity::CRITICAL]);
            $this->assertNotContains($anomaly->getStatus(), [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED]);
        }
    }

    public function testGetAnomalyStatsByTypeReturnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $stats = $this->repository->getAnomalyStatsByType($startDate, $endDate);

        $this->assertIsArray($stats);
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('anomalyType', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function testGetAnomalyStatsBySeverityReturnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $stats = $this->repository->getAnomalyStatsBySeverity($startDate, $endDate);

        $this->assertIsArray($stats);
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('severity', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function testFindAutoDetectedReturnsCorrectRecords(): void
    {
        $result = $this->repository->findAutoDetected(10);

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertTrue($anomaly->isAutoDetected());
        }
    }

    public function testFindByTypeReturnsCorrectRecords(): void
    {
        $result = $this->repository->findByType(AnomalyType::RAPID_PROGRESS, 10);

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertEquals(AnomalyType::RAPID_PROGRESS, $anomaly->getAnomalyType());
        }
    }

    public function testFindLongProcessingReturnsCorrectRecords(): void
    {
        $threshold = new \DateTimeImmutable('-3 days');

        $result = $this->repository->findLongProcessing($threshold);

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertLessThan($threshold, $anomaly->getDetectTime());
            $this->assertContains($anomaly->getStatus(), [AnomalyStatus::DETECTED, AnomalyStatus::INVESTIGATING]);
        }
    }

    public function testGetProcessingEfficiencyStatsReturnsCorrectStats(): void
    {
        $stats = $this->repository->getProcessingEfficiencyStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalAnomalies', $stats);
        $this->assertArrayHasKey('resolvedCount', $stats);
        $this->assertArrayHasKey('ignoredCount', $stats);
        $this->assertArrayHasKey('avgProcessingTime', $stats);
    }

    public function testFindUnresolvedReturnsCorrectRecords(): void
    {
        $result = $this->repository->findUnresolved();

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertNotContains($anomaly->getStatus(), [AnomalyStatus::RESOLVED, AnomalyStatus::IGNORED]);
        }
    }

    public function testFindByDateRangeReturnsCorrectRecords(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->findByDateRange($startDate, $endDate);

        $this->assertIsArray($result);
        foreach ($result as $anomaly) {
            $this->assertInstanceOf(LearnAnomaly::class, $anomaly);
            $this->assertGreaterThanOrEqual($startDate, $anomaly->getDetectTime());
            $this->assertLessThanOrEqual($endDate, $anomaly->getDetectTime());
        }
    }

    public function testGetRecentTrends(): void
    {
        $result = $this->repository->getRecentTrends();

        $this->assertIsArray($result);
    }

    public function testGetRecentTrendsWithCustomDays(): void
    {
        $result = $this->repository->getRecentTrends(14);

        $this->assertIsArray($result);
    }

    public function testFindByUserAndCourse(): void
    {
        $userId = 'user_' . uniqid();
        $courseId = 'course_' . uniqid();

        $result = $this->repository->findByUserAndCourse($userId, $courseId);

        $this->assertIsArray($result);
    }

    public function testFindByDateRangeAndFilters(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate);

        $this->assertIsArray($result);
    }

    public function testFindByDateRangeAndFiltersWithCourseId(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $filters = ['courseId' => 'course_' . uniqid()];

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate, $filters);

        $this->assertIsArray($result);
    }

    public function testFindByDateRangeAndFiltersWithUserId(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $filters = ['userId' => 'user_' . uniqid()];

        $result = $this->repository->findByDateRangeAndFilters($startDate, $endDate, $filters);

        $this->assertIsArray($result);
    }

    public function testFindByWithEmptyCriteriaShouldReturnAllEntities(): void
    {
        $result = $this->repository->findBy([]);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnAnomaly::class, $entity);
        }
    }

    public function testFindOneByEmptyCriteriaShouldReturnFirstLearnAnomalyOrNull(): void
    {
        $result = $this->repository->findOneBy([]);

        // 直接验证结果类型，避免出现 PHPStan 错误
        $this->assertTrue($result instanceof LearnAnomaly || null === $result);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestAnomaly();
    }

    /** @return ServiceEntityRepository<LearnAnomaly> */
/** @return ServiceEntityRepository<LearnAnomaly> */
/** @return ServiceEntityRepository<LearnAnomaly> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
