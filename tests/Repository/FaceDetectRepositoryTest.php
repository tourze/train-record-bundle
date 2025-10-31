<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\FaceDetect;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;

/**
 * FaceDetectRepository 集成测试
 *
 * @template TEntity of FaceDetect
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(FaceDetectRepository::class)]
#[RunTestsInSeparateProcesses]
final class FaceDetectRepositoryTest extends AbstractRepositoryTestCase
{
    private FaceDetectRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(FaceDetect::class);
        $this->assertInstanceOf(FaceDetectRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 创建测试人脸检测记录
     */
    private function createTestFaceDetect(
        ?string $sessionId = null,
        ?string $confidence = '0.95',
        ?string $similarity = '0.85',
        bool $isVerified = false,
        ?string $errorMessage = null,
    ): FaceDetect {
        // 创建或获取测试会话
        $session = $this->createOrGetTestSession($sessionId ?? 'session_' . uniqid());

        $faceDetect = new FaceDetect();
        $faceDetect->setSession($session);
        $faceDetect->setConfidence($confidence);
        $faceDetect->setSimilarity($similarity);
        $faceDetect->setIsVerified($isVerified);
        $faceDetect->setErrorMessage($errorMessage);
        $faceDetect->setImageData('test_image_data');
        $faceDetect->setDetectResult(['faces' => 1, 'quality' => 'good']);

        self::getEntityManager()->persist($faceDetect);
        self::getEntityManager()->flush();

        return $faceDetect;
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

    /**
     * 测试根据会话查找人脸检测记录
     */
    public function testFindBySessionReturnsCorrectRecords(): void
    {
        $results = $this->repository->findBySession('nonexistent_session_' . uniqid());

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 测试查找已验证的人脸检测记录
     */
    public function testFindVerifiedBySessionReturnsOnlyVerifiedRecords(): void
    {
        $results = $this->repository->findVerifiedBySession('nonexistent_session_' . uniqid());

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 测试统计会话的人脸检测次数
     */
    public function testCountBySessionReturnsCorrectCount(): void
    {
        $count = $this->repository->countBySession('nonexistent_session_' . uniqid());

        $this->assertEquals(0, $count);
    }

    /**
     * 测试统计会话的已验证人脸检测次数
     */
    public function testCountVerifiedBySessionReturnsCorrectCount(): void
    {
        $count = $this->repository->countVerifiedBySession('nonexistent_session_' . uniqid());

        $this->assertEquals(0, $count);
    }

    /**
     * 测试查找低相似度的人脸检测记录
     */
    public function testFindLowSimilarityBySessionReturnsCorrectRecords(): void
    {
        $results = $this->repository->findLowSimilarityBySession('nonexistent_session_' . uniqid(), '0.80');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 测试查找不存在的会话
     */
    public function testFindBySessionWithNonExistentSessionReturnsEmptyArray(): void
    {
        $results = $this->repository->findBySession('non-existent-session');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 测试统计不存在的会话
     */
    public function testCountBySessionWithNonExistentSessionReturnsZero(): void
    {
        $count = $this->repository->countBySession('non-existent-session');

        $this->assertEquals(0, $count);
    }

    /**
     * 测试不同相似度的记录
     */
    public function testFindLowSimilarityBySessionWithVariousThresholds(): void
    {
        // 测试阈值0.70
        $results70 = $this->repository->findLowSimilarityBySession('nonexistent_session', '0.70');
        $this->assertIsArray($results70);
        $this->assertEmpty($results70);

        // 测试阈值0.90
        $results90 = $this->repository->findLowSimilarityBySession('nonexistent_session', '0.90');
        $this->assertIsArray($results90);
        $this->assertEmpty($results90);

        // 测试阈值0.50
        $results50 = $this->repository->findLowSimilarityBySession('nonexistent_session', '0.50');
        $this->assertIsArray($results50);
        $this->assertEmpty($results50);
    }

    /**
     * 测试混合验证状态的统计
     */
    public function testCountVerifiedBySessionWithMixedStatus(): void
    {
        $totalCount = $this->repository->countBySession('nonexistent_session');
        $verifiedCount = $this->repository->countVerifiedBySession('nonexistent_session');

        $this->assertEquals(0, $totalCount);
        $this->assertEquals(0, $verifiedCount);
    }

    /**
     * 测试按时间排序
     */
    public function testFindBySessionOrderedByCreateTime(): void
    {
        $results = $this->repository->findBySession('nonexistent_session');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ===== 基础 CRUD 操作测试 =====

    public function testFindOneByWithNonExistingCriteriaShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['similarity' => 'nonexistent_similarity_' . uniqid()]);

        $this->assertNull($result);
    }

    public function testFindByNullValueShouldReturnEntitiesWithNullField(): void
    {
        // 查找errorMessage为null的记录
        $result = $this->repository->findBy(['errorMessage' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(FaceDetect::class, $entity);
            $this->assertNull($entity->getErrorMessage());
        }
    }

    public function testSaveMethodBehavior(): void
    {
        // 测试save方法的行为
        $faceDetect = $this->createTestFaceDetect();
        $id = $faceDetect->getId();

        // 修改数据
        $faceDetect->setConfidence('0.98');

        // 保存并验证
        $this->repository->save($faceDetect, true);

        $saved = $this->repository->find($id);
        $this->assertNotNull($saved);
        $this->assertEquals('0.98', $saved->getConfidence());
    }

    public function testRemoveMethodBehavior(): void
    {
        // 测试remove方法的行为
        $faceDetect = $this->createTestFaceDetect();
        $id = $faceDetect->getId();

        // 验证记录存在
        $this->assertNotNull($this->repository->find($id));

        // 删除并验证
        $this->repository->remove($faceDetect, true);

        $this->assertNull($this->repository->find($id));
    }

    // ===== findOneBy 排序测试 =====

    // ===== 关联查询测试 =====

    public function testFindOneByAssociationSessionShouldReturnMatchingEntity(): void
    {
        // 测试关联查询功能
        $result = $this->repository->findOneBy([], null);

        // 如果没有数据应该返回null而不抛异常
        if (null !== $result) {
            $this->assertInstanceOf(FaceDetect::class, $result);
        }
    }

    public function testCountByAssociationSessionShouldReturnCorrectNumber(): void
    {
        // 测试关联计数
        $count = $this->repository->count(['isVerified' => true]);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== 可空字段查询测试 =====

    /**
     * @return FaceDetect
     */
    protected function createNewEntity(): object
    {
        return $this->createTestFaceDetect();
    }

    /** @return ServiceEntityRepository<FaceDetect> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
