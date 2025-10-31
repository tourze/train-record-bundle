<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnProgress;
use Tourze\TrainRecordBundle\Repository\LearnProgressRepository;

/**
 * LearnProgressRepository 集成测试
 *
 * @template TEntity of LearnProgress
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnProgressRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnProgressRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnProgressRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnProgress::class);
        $this->assertInstanceOf(LearnProgressRepository::class, $repository);
        $this->repository = $repository;
    }

    /** @return ServiceEntityRepository<LearnProgress> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
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
        ?\DateTimeInterface $lastUpdateTime = null,
        bool $flush = true,
    ): LearnProgress {
        $learnProgress = new LearnProgress();
        $learnProgress->setUserId($userId);

        // Create mock Course and Lesson entities for testing
        $mockCourse = $this->createMock(Course::class);
        $mockCourse->method('getId')->willReturn($course);
        $mockCourse->method('getTitle')->willReturn('Test Course ' . $course);

        $mockLesson = $this->createMock(Lesson::class);
        $mockLesson->method('getId')->willReturn($lesson);
        $mockLesson->method('getTitle')->willReturn('Test Lesson ' . $lesson);

        $learnProgress->setCourse($mockCourse);
        $learnProgress->setLesson($mockLesson);
        $learnProgress->setProgress($progress);
        $learnProgress->setIsCompleted($isCompleted);
        $learnProgress->setWatchedDuration($watchedDuration);
        $learnProgress->setEffectiveDuration($effectiveDuration);
        $learnProgress->setQualityScore($qualityScore);
        $learnProgress->setLastPosition(900);
        $learnProgress->setLastUpdateTime($lastUpdateTime instanceof \DateTimeImmutable ? $lastUpdateTime :
            (null !== $lastUpdateTime ? new \DateTimeImmutable($lastUpdateTime->format('Y-m-d H:i:s')) : new \DateTimeImmutable()));

        self::getEntityManager()->persist($learnProgress);
        if ($flush) {
            self::getEntityManager()->flush();
        }

        return $learnProgress;
    }

    /**
     * 测试根据用户和课程查找进度
     */
    public function testFindByUserAndCourseReturnsCorrectRecords(): void
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
    public function testFindByUserAndLessonReturnsCorrectRecord(): void
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
    public function testFindByUserAndLessonWithNonExistentReturnsNull(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');

        $result = $this->repository->findByUserAndLesson('user001', 'lesson999');

        $this->assertNull($result);
    }

    /**
     * 测试查找用户已完成的进度
     */
    public function testFindCompletedByUserReturnsOnlyCompletedRecords(): void
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
    public function testGetCourseCompletionStatsReturnsCorrectStats(): void
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
    public function testFindNeedingSyncReturnsRecordsAfterSyncTime(): void
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
    public function testFindLowQualityProgressReturnsLowQualityRecords(): void
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
    public function testGetLearningEfficiencyStatsReturnsCorrectStats(): void
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
    public function testFindRecentlyUpdatedReturnsLimitedRecords(): void
    {
        // 使用批量操作和明确的时间戳
        $progresses = [];
        $baseTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        for ($i = 1; $i <= 10; ++$i) {
            $updateTime = $baseTime->add(new \DateInterval('PT' . $i . 'S'));
            $progresses[] = $this->createTestProgress('user' . $i, 'course001', 'lesson' . $i, 50.0, false, 1800, 1500, null, $updateTime, false);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findRecentlyUpdated(5);

        $this->assertCount(5, $results);
        // 验证按更新时间降序排列
        $previousTime = null;
        foreach ($results as $progress) {
            if (null !== $previousTime) {
                $this->assertLessThanOrEqual($previousTime, $progress->getLastUpdateTime());
            }
            $previousTime = $progress->getLastUpdateTime();
        }
    }

    /**
     * 测试批量更新有效时长
     */
    public function testBatchUpdateEffectiveDurationUpdatesCorrectly(): void
    {
        $progress1 = $this->createTestProgress('user001', 'course001', 'lesson001');
        $progress2 = $this->createTestProgress('user002', 'course002', 'lesson002');
        $progress3 = $this->createTestProgress('user003', 'course003', 'lesson003');

        $progressIds = [
            (int) $progress1->getId(),
            (int) $progress2->getId(),
        ];
        $effectiveDurations = [2500, 3500];

        $this->repository->batchUpdateEffectiveDuration($progressIds, $effectiveDurations);
        self::getEntityManager()->clear();

        $updatedProgress1 = $this->repository->find($progress1->getId());
        $updatedProgress2 = $this->repository->find($progress2->getId());
        $updatedProgress3 = $this->repository->find($progress3->getId());

        $this->assertNotNull($updatedProgress1);
        $this->assertNotNull($updatedProgress2);
        $this->assertNotNull($updatedProgress3);
        $this->assertEquals(2500, $updatedProgress1->getEffectiveDuration());
        $this->assertEquals(3500, $updatedProgress2->getEffectiveDuration());
        $this->assertEquals(1500, $updatedProgress3->getEffectiveDuration()); // 未更新
    }

    /**
     * 测试更新进度
     */
    public function testUpdateProgressPersistsChanges(): void
    {
        $progress = $this->createTestProgress('user001', 'course001', 'lesson001', 50.0);

        $progress->setProgress(75.0);
        $progress->setWatchedDuration(2700);

        $this->repository->updateProgress($progress);
        self::getEntityManager()->clear();

        $updatedProgress = $this->repository->find($progress->getId());

        $this->assertNotNull($updatedProgress);
        $this->assertEquals(75.0, $updatedProgress->getProgress());
        $this->assertEquals(2700, $updatedProgress->getWatchedDuration());
    }

    /**
     * 测试根据课程查找进度
     */
    public function testFindByCourseReturnsAllCourseProgress(): void
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
    public function testCalculateCompletionRateByFiltersReturnsCorrectRate(): void
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
    public function testCalculateCompletionRateByFiltersWithNoDataReturnsZero(): void
    {
        $rate = $this->repository->calculateCompletionRateByFilters(['userId' => 'user999']);

        $this->assertEquals(0.0, $rate);
    }

    /**
     * 测试按日期范围统计完成数
     */
    public function testCountCompletionsByDateRangeReturnsCorrectCount(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        // 在范围内的完成记录
        $progress1 = new LearnProgress();
        $progress1->setUserId('user001');
        // TODO: setCourseId is deprecated
        // $progress1->setCourseId('course001');
        // TODO: setLessonId is deprecated
        // $progress1->setLessonId('lesson001');
        $progress1->setProgress(100.0);
        $progress1->setIsCompleted(true);
        $progress1->setWatchedDuration(1800);
        $progress1->setEffectiveDuration(1500);

        // createTime 由 Doctrine 自动管理

        self::getEntityManager()->persist($progress1);

        // 在范围内但未完成的记录
        $progress2 = new LearnProgress();
        $progress2->setUserId('user002');
        // TODO: setCourseId is deprecated
        // $progress2->setCourseId('course002');
        // TODO: setLessonId is deprecated
        // $progress2->setLessonId('lesson002');
        $progress2->setProgress(50.0);
        $progress2->setIsCompleted(false);
        $progress2->setWatchedDuration(900);
        $progress2->setEffectiveDuration(800);

        // 由于 createTime 由 Doctrine 自动管理，我们直接使用当前时间

        self::getEntityManager()->persist($progress2);

        self::getEntityManager()->flush();

        $count = $this->repository->countCompletionsByDateRange($startDate, $endDate);

        $this->assertEquals(1, $count);
    }

    /**
     * 测试根据用户查找学习进度
     */
    public function testFindByUser(): void
    {
        $userId = 'user_' . uniqid();

        $result = $this->repository->findByUser($userId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试根据用户和日期范围查找学习进度
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
     * 测试按日期范围和过滤条件查找进度
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
     * 测试按日期范围和过滤条件查找进度（带课程ID）
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
     * 测试按日期范围和过滤条件查找进度（带用户ID）
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
        $this->createTestProgress('user001', 'course001', 'lesson001');

        $result = $this->repository->findOneBy(['userId' => 'user999']);

        $this->assertNull($result);
    }

    /**
     * 测试 save 方法
     */
    public function testSave(): void
    {
        $progress = new LearnProgress();
        $progress->setUserId('user001');
        // TODO: setCourseId is deprecated
        // $progress->setCourseId('course001');
        // TODO: setLessonId is deprecated
        // $progress->setLessonId('lesson001');
        $progress->setProgress(50.0);
        $progress->setIsCompleted(false);
        $progress->setWatchedDuration(1800);
        $progress->setEffectiveDuration(1500);
        $progress->setLastPosition(900);

        $this->repository->save($progress, true);

        $this->assertNotNull($progress->getId());

        $found = $this->repository->find($progress->getId());
        $this->assertNotNull($found);
        $this->assertEquals('user001', $found->getUserId());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemove(): void
    {
        $progress = $this->createTestProgress('user001', 'course001', 'lesson001');
        $id = $progress->getId();

        $this->repository->remove($progress, true);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    /**
     * 测试查询可空字段
     */
    public function testFindByNullableFields(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 50.0, false, 1800, 1500, null);
        $this->createTestProgress('user002', 'course002', 'lesson002', 60.0, false, 2000, 1800, 8.5);

        $resultsWithNull = $this->repository->findBy(['qualityScore' => null]);
        $this->assertCount(1, $resultsWithNull);

        $resultsWithValue = $this->repository->findBy(['isCompleted' => false]);
        $this->assertCount(2, $resultsWithValue);
    }

    /**
     * 测试 count IS NULL 查询可空字段
     */
    public function testCountWithNullableFieldsISNull(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 50.0, false, 1800, 1500, null);
        $this->createTestProgress('user002', 'course002', 'lesson002', 60.0, false, 2000, 1800, 8.5);
        $this->createTestProgress('user003', 'course003', 'lesson003', 70.0, false, 2200, 2000, null);

        $countWithNullQualityScore = $this->repository->count(['qualityScore' => null]);
        $this->assertEquals(2, $countWithNullQualityScore);
    }

    /**
     * 测试 findOneBy 排序逻辑
     */
    public function testFindOneByWithOrderBy(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001', 30.0);
        $this->createTestProgress('user001', 'course002', 'lesson002', 70.0);
        $this->createTestProgress('user001', 'course003', 'lesson003', 50.0);

        $result = $this->repository->findOneBy(['userId' => 'user001'], ['progress' => 'DESC']);

        $this->assertNotNull($result);
        $this->assertEquals(70.0, $result->getProgress());
    }

    /**
     * 测试查询关联字段 Course
     */
    public function testFindByAssociationCourse(): void
    {
        $progress1 = $this->createTestProgress('user001', 'course001', 'lesson001');
        $progress2 = $this->createTestProgress('user002', 'course002', 'lesson002');

        $results = $this->repository->findBy(['course' => $progress1->getCourse()]);

        $this->assertCount(1, $results);
        $this->assertEquals($progress1->getId(), $results[0]->getId());
    }

    /**
     * 测试查询关联字段 Lesson
     */
    public function testFindByAssociationLesson(): void
    {
        $progress1 = $this->createTestProgress('user001', 'course001', 'lesson001');
        $progress2 = $this->createTestProgress('user002', 'course002', 'lesson002');

        $results = $this->repository->findBy(['lesson' => $progress1->getLesson()]);

        $this->assertCount(1, $results);
        $this->assertEquals($progress1->getId(), $results[0]->getId());
    }

    /**
     * 测试 count 关联查询 Course
     */
    public function testCountByAssociationCourse(): void
    {
        $progress1 = $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user002', 'course002', 'lesson002');

        $count = $this->repository->count(['course' => $progress1->getCourse()]);

        $this->assertEquals(1, $count);
    }

    /**
     * 测试 IS NULL 查询可空字段 watchedSegments
     */
    public function testFindByWatchedSegmentsIsNull(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user002', 'course002', 'lesson002');

        $results = $this->repository->findBy(['watchedSegments' => null]);

        $this->assertCount(2, $results);
    }

    /**
     * 测试 IS NULL 查询可空字段 progressHistory
     */
    public function testFindByProgressHistoryIsNull(): void
    {
        $this->createTestProgress('user001', 'course001', 'lesson001');
        $this->createTestProgress('user002', 'course002', 'lesson002');

        $results = $this->repository->findBy(['progressHistory' => null]);

        $this->assertCount(2, $results);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestProgress();
    }
}
