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
use Tourze\TrainRecordBundle\Entity\FaceDetect;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\FaceDetectRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * FaceDetectRepository 集成测试
 */
class FaceDetectRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FaceDetectRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(FaceDetect::class);
        $this->assertInstanceOf(FaceDetectRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . FaceDetect::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试人脸检测记录
     */
    private function createTestFaceDetect(
        string $sessionId = 'session001',
        bool $isVerified = true,
        ?string $similarity = '0.95',
        ?string $imageUrl = null
    ): FaceDetect {
        $faceDetect = new FaceDetect();
        $faceDetect->setSession($this->createMockSession($sessionId));
        $faceDetect->setIsVerified($isVerified);
        $faceDetect->setSimilarity($similarity);
        $faceDetect->setImageData($imageUrl ?? 'base64-encoded-image-data-' . uniqid());
        $faceDetect->setDetectResult([
            'success' => true,
            'similarity' => $similarity,
            'face_count' => 1
        ]);

        $this->entityManager->persist($faceDetect);
        $this->entityManager->flush();

        return $faceDetect;
    }

    /**
     * 创建模拟学习会话
     */
    private function createMockSession(string $sessionId): LearnSession
    {
        $session = new LearnSession();
        // 使用反射设置 ID
        $reflection = new \ReflectionClass($session);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($session, $sessionId);
        }
        return $session;
    }

    /**
     * 测试根据会话查找人脸检测记录
     */
    public function test_findBySession_returnsCorrectRecords(): void
    {
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', false);
        $this->createTestFaceDetect('session002', true);

        $results = $this->repository->findBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('session001', $record->getSession());
        }
    }

    /**
     * 测试查找已验证的人脸检测记录
     */
    public function test_findVerifiedBySession_returnsOnlyVerifiedRecords(): void
    {
        $this->createTestFaceDetect('session001', true, '0.95');
        $this->createTestFaceDetect('session001', false, '0.75');
        $this->createTestFaceDetect('session001', true, '0.90');

        $results = $this->repository->findVerifiedBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertTrue($record->getIsVerified());
        }
    }

    /**
     * 测试统计会话的人脸检测次数
     */
    public function test_countBySession_returnsCorrectCount(): void
    {
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', false);
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session002', true);

        $count = $this->repository->countBySession('session001');

        $this->assertEquals(3, $count);
    }

    /**
     * 测试统计会话的已验证人脸检测次数
     */
    public function test_countVerifiedBySession_returnsCorrectCount(): void
    {
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', false);
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session002', true);

        $count = $this->repository->countVerifiedBySession('session001');

        $this->assertEquals(2, $count);
    }

    /**
     * 测试查找低相似度的人脸检测记录
     */
    public function test_findLowSimilarityBySession_returnsCorrectRecords(): void
    {
        $this->createTestFaceDetect('session001', true, '0.95');
        $this->createTestFaceDetect('session001', true, '0.75');
        $this->createTestFaceDetect('session001', true, '0.65');
        $this->createTestFaceDetect('session001', true, null); // null相似度也应该返回

        $results = $this->repository->findLowSimilarityBySession('session001', '0.80');

        $this->assertCount(3, $results); // 0.75, 0.65 和 null
        foreach ($results as $record) {
            $similarity = $record->getSimilarity();
            $this->assertTrue($similarity === null || (float)$similarity < 0.80);
        }
    }

    /**
     * 测试查找不存在的会话
     */
    public function test_findBySession_withNonExistentSession_returnsEmptyArray(): void
    {
        $this->createTestFaceDetect('session001', true);

        $results = $this->repository->findBySession('non-existent-session');

        $this->assertEmpty($results);
    }

    /**
     * 测试统计不存在的会话
     */
    public function test_countBySession_withNonExistentSession_returnsZero(): void
    {
        $this->createTestFaceDetect('session001', true);

        $count = $this->repository->countBySession('non-existent-session');

        $this->assertEquals(0, $count);
    }

    /**
     * 测试不同相似度的记录
     */
    public function test_findLowSimilarityBySession_withVariousThresholds(): void
    {
        $this->createTestFaceDetect('session001', true, '0.95');
        $this->createTestFaceDetect('session001', true, '0.85');
        $this->createTestFaceDetect('session001', true, '0.75');
        $this->createTestFaceDetect('session001', true, '0.65');
        $this->createTestFaceDetect('session001', true, '0.55');

        // 测试阈值0.70
        $results70 = $this->repository->findLowSimilarityBySession('session001', '0.70');
        $this->assertCount(2, $results70); // 0.65 和 0.55

        // 测试阈值0.90
        $results90 = $this->repository->findLowSimilarityBySession('session001', '0.90');
        $this->assertCount(4, $results90); // 0.85, 0.75, 0.65 和 0.55

        // 测试阈值0.50
        $results50 = $this->repository->findLowSimilarityBySession('session001', '0.50');
        $this->assertCount(0, $results50); // 没有低于0.50的
    }

    /**
     * 测试混合验证状态的统计
     */
    public function test_countVerifiedBySession_withMixedStatus(): void
    {
        // 创建5个记录，3个已验证，2个未验证
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', false);
        $this->createTestFaceDetect('session001', true);
        $this->createTestFaceDetect('session001', false);

        $totalCount = $this->repository->countBySession('session001');
        $verifiedCount = $this->repository->countVerifiedBySession('session001');

        $this->assertEquals(5, $totalCount);
        $this->assertEquals(3, $verifiedCount);
    }

    /**
     * 测试按时间排序
     */
    public function test_findBySession_orderedByCreateTime(): void
    {
        // 创建记录时添加延迟以确保时间差异
        $detect1 = $this->createTestFaceDetect('session001', true, '0.95');
        usleep(1000);
        $detect2 = $this->createTestFaceDetect('session001', true, '0.90');
        usleep(1000);
        $detect3 = $this->createTestFaceDetect('session001', true, '0.85');

        $results = $this->repository->findBySession('session001');

        $this->assertCount(3, $results);
        // 验证按创建时间升序排列
        $this->assertEquals('0.95', $results[0]->getSimilarity());
        $this->assertEquals('0.90', $results[1]->getSimilarity());
        $this->assertEquals('0.85', $results[2]->getSimilarity());
    }
}