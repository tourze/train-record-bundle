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
use Tourze\TrainRecordBundle\Entity\LearnProgress;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnProgressRepository 集成测试
 */
class LearnProgressRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnProgressRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(LearnProgress::class);
        $this->assertInstanceOf(LearnProgressRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnProgress::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试进度记录
     */
    private function createTestProgress(
        string $userId = 'user001',
        string $course = 'course001',
        string $lesson = 'lesson001',
        float $progress = 50.0,
        bool $isCompleted = false,
        float $watchedDuration = 1800,
        float $effectiveDuration = 1500,
        ?float $qualityScore = null,
        ?\DateTimeInterface $lastUpdateTime = null
    ): LearnProgress {
        $learnProgress = new LearnProgress();
        $learnProgress->setUserId($userId);
        $learnProgress->setCourse($course);
        $learnProgress->setLesson($lesson);
        $learnProgress->setProgress($progress);
        $learnProgress->setIsCompleted($isCompleted);
        $learnProgress->setWatchedDuration($watchedDuration);
        $learnProgress->setEffectiveDuration($effectiveDuration);
        $learnProgress->setQualityScore($qualityScore);
        $learnProgress->setLastPosition(900);
        $learnProgress->setLastUpdateTime($lastUpdateTime ?? new \DateTimeImmutable());

        $this->entityManager->persist($learnProgress);
        $this->entityManager->flush();

        return $learnProgress;
    }

    /**
     * 测试根据用户和课程查找进度
     */
    public function test_findByUserAndCourse_returnsCorrectRecords(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user001', 'course001', 'lesson002');
        $this->createTestProgress('user001', 'course002', 'lesson003');
        $this->createTestProgress('user002', 'course001', 'lesson001');

        $results = $this->repository->findByUserAndCourse('user001', 'course001');

        $this->assertCount(2, $results);
        foreach ($results as $progress) {
            $this->assertEquals('user001', $progress->getUserId());
            $this->assertEquals('course001', $progress->getCourse());
        }
    }

    /**
     * 测试根据用户和课时查找进度
     */
    public function test_findByUserAndLesson_returnsCorrectRecord(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user001', 'course001', 'lesson002');
        $this->createTestProgress('user002', 'course001', 'lesson001');

        $result = $this->repository->findByUserAndLesson('user001', 'lesson001');

        $this->assertNotNull($result);
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('lesson001', $result->getLesson());
    }

    /**
     * 测试查找不存在的用户课时进度
     */
    public function test_findByUserAndLesson_withNonExistent_returnsNull(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');

        $result = $this->repository->findByUserAndLesson('user001', 'lesson999');

        $this->assertNull($result);
    }

    /**
     * 测试查找用户已完成的进度
     */
    public function test_findCompletedByUser_returnsOnlyCompletedRecords(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 100.0, true);
        $this->createTestProgress('user001', 'course001', 'lesson002', 50.0, false);
        $this->createTestProgress('user001', 'course002', 'lesson003', 100.0, true);
        $this->createTestProgress('user002', 'course001', 'lesson001', 100.0, true);

        $results = $this->repository->findCompletedByUser('user001');

        $this->assertCount(2, $results);
        foreach ($results as $progress) {
            $this->assertEquals('user001', $progress->getUserId());
            $this->assertTrue($progress->getIsCompleted());
            $this->assertEquals(100.0, $progress->getProgress());
        }
    }

    /**
     * 测试获取课程完成统计
     */
    public function test_getCourseCompletionStats_returnsCorrectStats(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 100.0, true);
        $this->createTestProgress('user001', 'course001', 'lesson002', 100.0, true);
        $this->createTestProgress('user001', 'course001', 'lesson003', 60.0, false);
        $this->createTestProgress('user001', 'course001', 'lesson004', 40.0, false);

        $stats = $this->repository->getCourseCompletionStats('user001', 'course001');

        $this->assertEquals(4, $stats['totalLessons']);
        $this->assertEquals(2, $stats['completedLessons']);
        $this->assertEquals(75.0, $stats['avgProgress']); // (100+100+60+40)/4
    }

    /**
     * 测试查找需要同步的进度记录
     */
    public function test_findNeedingSync_returnsRecordsAfterSyncTime(): void
    {
        $lastSyncTime = new \DateTimeImmutable('-1 hour');
        
        $this->createTestProgress('user001', 'course001', 'lesson001', 50.0, false, 1800, 1500, null, new \DateTimeImmutable('-2 hours'));
        $this->createTestProgress('user002', 'course002', 'lesson002', 60.0, false, 2000, 1800, null, new \DateTimeImmutable('-30 minutes'));
        $this->createTestProgress('user003', 'course003', 'lesson003', 70.0, false, 2200, 2000, null, new \DateTimeImmutable('-10 minutes'));

        $results = $this->repository->findNeedingSync($lastSyncTime);

        $this->assertCount(2, $results);
        foreach ($results as $progress) {
            $this->assertGreaterThan($lastSyncTime, $progress->getLastUpdateTime());
        }
    }

    /**
     * 测试查找低质量学习记录
     */
    public function test_findLowQualityProgress_returnsLowQualityRecords(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 50.0, false, 1800, 1500, 3.5);
        $this->createTestProgress('user002', 'course002', 'lesson002', 60.0, false, 2000, 1800, 4.8);
        $this->createTestProgress('user003', 'course003', 'lesson003', 70.0, false, 2200, 2000, 8.5);
        $this->createTestProgress('user004', 'course004', 'lesson004', 80.0, false, 2400, 2200, null);

        $results = $this->repository->findLowQualityProgress(5.0);

        $this->assertCount(2, $results);
        foreach ($results as $progress) {
            $this->assertNotNull($progress->getQualityScore());
            $this->assertLessThan(5.0, $progress->getQualityScore());
        }
    }

    /**
     * 测试获取学习效率统计
     */
    public function test_getLearningEfficiencyStats_returnsCorrectStats(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 50.0, false, 2000, 1800); // 90% 效率
        $this->createTestProgress('user002', 'course002', 'lesson002', 60.0, false, 3000, 2400); // 80% 效率
        $this->createTestProgress('user003', 'course003', 'lesson003', 70.0, false, 4000, 2800); // 70% 效率

        $stats = $this->repository->getLearningEfficiencyStats();

        $this->assertEquals(3, $stats['totalRecords']);
        $this->assertEqualsWithDelta(0.8, $stats['avgEfficiency'], 0.01); // (0.9+0.8+0.7)/3
        $this->assertEqualsWithDelta(0.7, $stats['minEfficiency'], 0.01);
        $this->assertEqualsWithDelta(0.9, $stats['maxEfficiency'], 0.01);
    }

    /**
     * 测试查找最近更新的进度记录
     */
    public function test_findRecentlyUpdated_returnsLimitedRecords(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->createTestProgress('user' . $i, 'course001', 'lesson' . $i);
            usleep(1000); // 确保时间差异
        }

        $results = $this->repository->findRecentlyUpdated(5);

        $this->assertCount(5, $results);
        // 验证按更新时间降序排列
        $previousTime = null;
        foreach ($results as $progress) {
            if ($previousTime !== null) {
                $this->assertLessThanOrEqual($previousTime, $progress->getLastUpdateTime());
            }
            $previousTime = $progress->getLastUpdateTime();
        }
    }

    /**
     * 测试批量更新有效时长
     */
    public function test_batchUpdateEffectiveDuration_updatesCorrectly(): void
    {
        $progress1 = $this->createTestProgress('user001', 'course001', 'lesson001');
        $progress2 = $this->createTestProgress('user002', 'course002', 'lesson002');
        $progress3 = $this->createTestProgress('user003', 'course003', 'lesson003');

        $progressIds = [$progress1->getId(), $progress2->getId()];
        $effectiveDurations = [2500, 3500];

        $this->repository->batchUpdateEffectiveDuration($progressIds, $effectiveDurations);
        $this->entityManager->clear();

        $updatedProgress1 = $this->repository->find($progress1->getId());
        $updatedProgress2 = $this->repository->find($progress2->getId());
        $updatedProgress3 = $this->repository->find($progress3->getId());

        $this->assertEquals(2500, $updatedProgress1->getEffectiveDuration());
        $this->assertEquals(3500, $updatedProgress2->getEffectiveDuration());
        $this->assertEquals(1500, $updatedProgress3->getEffectiveDuration()); // 未更新
    }

    /**
     * 测试更新进度
     */
    public function test_updateProgress_persistsChanges(): void
    {
        $progress = $this->createTestProgress('user001', 'course001', 'lesson001', 50.0);
        
        $progress->setProgress(75.0);
        $progress->setWatchedDuration(2700);
        
        $this->repository->updateProgress($progress);
        $this->entityManager->clear();

        $updatedProgress = $this->repository->find($progress->getId());

        $this->assertEquals(75.0, $updatedProgress->getProgress());
        $this->assertEquals(2700, $updatedProgress->getWatchedDuration());
    }

    /**
     * 测试根据课程查找进度
     */
    public function test_findByCourse_returnsAllCourseProgress(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user002', 'course001', 'lesson001');
        $this->createTestProgress('user003', 'course001', 'lesson002');
        $this->createTestProgress('user004', 'course002', 'lesson003');

        $results = $this->repository->findByCourse('course001');

        $this->assertCount(3, $results);
        foreach ($results as $progress) {
            $this->assertEquals('course001', $progress->getCourse());
        }
    }

    /**
     * 测试计算完成率
     */
    public function test_calculateCompletionRateByFilters_returnsCorrectRate(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 100.0, true);
        $this->createTestProgress('user001', 'course001', 'lesson002', 100.0, true);
        $this->createTestProgress('user001', 'course001', 'lesson003', 60.0, false);
        $this->createTestProgress('user002', 'course001', 'lesson001', 100.0, true);

        $rate = $this->repository->calculateCompletionRateByFilters(['userId' => 'user001', 'courseId' => 'course001']);

        $this->assertEqualsWithDelta(66.67, $rate, 0.01); // 2/3 * 100
    }

    /**
     * 测试空数据完成率计算
     */
    public function test_calculateCompletionRateByFilters_withNoData_returnsZero(): void
    {
        $rate = $this->repository->calculateCompletionRateByFilters(['userId' => 'user999']);

        $this->assertEquals(0.0, $rate);
    }

    /**
     * 测试按日期范围统计完成数
     */
    public function test_countCompletionsByDateRange_returnsCorrectCount(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        // 在范围内的完成记录
        $progress1 = new LearnProgress();
        $progress1->setUserId('user001');
        $progress1->setCourse('course001');
        $progress1->setLesson('lesson001');
        $progress1->setProgress(100.0);
        $progress1->setIsCompleted(true);
        $progress1->setWatchedDuration(1800);
        $progress1->setEffectiveDuration(1500);
        
        // 设置创建时间在范围内
        $reflection = new \ReflectionClass($progress1);
        if ($reflection->hasProperty('createTime')) {
            $property = $reflection->getProperty('createTime');
            $property->setAccessible(true);
            $property->setValue($progress1, new \DateTimeImmutable('2024-01-15'));
        }
        
        $this->entityManager->persist($progress1);
        
        // 在范围内但未完成的记录
        $progress2 = new LearnProgress();
        $progress2->setUserId('user002');
        $progress2->setCourse('course002');
        $progress2->setLesson('lesson002');
        $progress2->setProgress(50.0);
        $progress2->setIsCompleted(false);
        $progress2->setWatchedDuration(900);
        $progress2->setEffectiveDuration(800);
        
        if ($reflection->hasProperty('createTime')) {
            $property = $reflection->getProperty('createTime');
            $property->setAccessible(true);
            $property->setValue($progress2, new \DateTimeImmutable('2024-01-20'));
        }
        
        $this->entityManager->persist($progress2);
        
        $this->entityManager->flush();

        $count = $this->repository->countCompletionsByDateRange($startDate, $endDate);

        $this->assertEquals(1, $count);
    }
}