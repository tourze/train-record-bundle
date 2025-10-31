<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;

/**
 * LearnArchiveRepository 集成测试
 *
 * @template TEntity of LearnArchive
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnArchiveRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnArchiveRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnArchiveRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnArchive::class);
        $this->assertInstanceOf(LearnArchiveRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 创建测试档案
     */
    private function createTestArchive(
        string $userId = 'user001',
        string $courseId = 'course001',
        ArchiveStatus $status = ArchiveStatus::ACTIVE,
        ?\DateTimeInterface $expiryTime = null,
        ?\DateTimeInterface $lastVerifyTime = null,
        ArchiveFormat $format = ArchiveFormat::JSON,
    ): LearnArchive {
        // 创建或获取 Course 实体
        $course = $this->createOrGetTestCourse($courseId);

        $archive = new LearnArchive();
        $archive->setUserId($userId);
        $archive->setCourse($course);
        $archive->setArchiveStatus($status);
        $archive->setArchiveFormat($format);
        $archive->setArchiveTime(new \DateTimeImmutable());
        $archive->setExpiryTime($expiryTime instanceof \DateTimeImmutable ? $expiryTime :
            (null !== $expiryTime ? new \DateTimeImmutable($expiryTime->format('Y-m-d H:i:s')) : new \DateTimeImmutable('+3 years')));
        $archive->setLastVerifyTime($lastVerifyTime instanceof \DateTimeImmutable ? $lastVerifyTime :
            (null !== $lastVerifyTime ? new \DateTimeImmutable($lastVerifyTime->format('Y-m-d H:i:s')) : null));
        $archive->setFileSize(1024 * 100); // 100KB
        $archive->setChecksum('abc123def456');
        $archive->setMetadata(['version' => '1.0']);

        self::getEntityManager()->persist($archive);
        self::getEntityManager()->flush();

        return $archive;
    }

    /**
     * 创建或获取测试课程
     */
    private function createOrGetTestCourse(string $courseId): Course
    {
        // 检查是否已经存在该 courseId 的课程
        $existingCourse = self::getEntityManager()
            ->getRepository(Course::class)
            ->find($courseId)
        ;

        if (null !== $existingCourse) {
            return $existingCourse;
        }

        // 创建或获取必需的 Catalog
        $category = $this->createOrGetTestCatalog();

        // 如果不存在，创建一个简单的课程用于测试
        $course = new Course();
        $course->setTitle("Test Course {$courseId}");
        $course->setCategory($category);
        $course->setLearnHour(10); // 设置默认学时
        $course->setValid(true);

        // 尝试持久化课程
        self::getEntityManager()->persist($course);
        self::getEntityManager()->flush();

        return $course;
    }

    private function createOrGetTestCatalog(): Catalog
    {
        // 检查是否已经存在测试分类
        $existingCatalog = self::getEntityManager()
            ->getRepository(Catalog::class)
            ->findOneBy(['name' => 'Test Catalog'])
        ;

        if (null !== $existingCatalog) {
            return $existingCatalog;
        }

        // 创建测试分类
        $category = new Catalog();
        $category->setName('Test Catalog');

        // 尝试持久化分类
        self::getEntityManager()->persist($category);
        self::getEntityManager()->flush();

        return $category;
    }

    /**
     * 测试根据用户查找档案
     */
    public function testFindByUserReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001');
        $this->createTestArchive('user001', 'course002');
        $this->createTestArchive('user002', 'course001');

        $results = $this->repository->findByUser('user001');

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertEquals('user001', $archive->getUserId());
        }
    }

    /**
     * 测试根据用户和课程查找档案
     */
    public function testFindByUserAndCourseReturnsCorrectRecord(): void
    {
        $this->createTestArchive('user001', 'course001');
        $this->createTestArchive('user001', 'course002');
        $this->createTestArchive('user002', 'course001');

        $result = $this->repository->findByUserAndCourse('user001', 'course001');

        $this->assertNotNull($result);
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('course001', $result->getCourseId());
    }

    /**
     * 测试查找不存在的用户和课程档案
     */
    public function testFindByUserAndCourseWithNonExistentReturnsNull(): void
    {
        $this->createTestArchive('user001', 'course001');

        $result = $this->repository->findByUserAndCourse('user001', 'course999');

        $this->assertNull($result);
    }

    /**
     * 测试查找已过期的档案
     */
    public function testFindExpiredReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 week'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+1 day'));

        $results = $this->repository->findExpired();

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThan(new \DateTimeImmutable(), $archive->getExpiryTime());
        }
    }

    /**
     * 测试根据状态查找档案
     */
    public function testFindByStatusReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED);

        $results = $this->repository->findByStatus(ArchiveStatus::ACTIVE);

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertEquals(ArchiveStatus::ACTIVE, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试档案统计
     */
    public function testGetArchiveStatsReturnsCorrectStats(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED);

        $stats = $this->repository->getArchiveStats();

        $this->assertEquals(4, $stats['totalArchives']);
        $this->assertEquals(2, $stats['activeCount']);
        $this->assertEquals(1, $stats['archivedCount']);
        $this->assertEquals(1, $stats['expiredCount']);
    }

    /**
     * 测试查找需要验证的档案
     */
    public function testFindNeedVerificationReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, null, null);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, null, new \DateTimeImmutable('-2 months'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, null, new \DateTimeImmutable('-2 weeks'));

        $results = $this->repository->findNeedVerification();

        $this->assertCount(2, $results);
        // 验证返回的是需要验证的档案（null或超过1个月）
        foreach ($results as $archive) {
            $lastVerification = $archive->getLastVerifyTime();
            $this->assertTrue(
                null === $lastVerification
                || $lastVerification < new \DateTimeImmutable('-1 month')
            );
        }
    }

    /**
     * 测试查找即将过期的档案
     */
    public function testFindExpiringSoonReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+10 days'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+20 days'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+40 days'));
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED, new \DateTimeImmutable('+10 days'));

        $results = $this->repository->findExpiringSoon(30);

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThanOrEqual(new \DateTimeImmutable('+30 days'), $archive->getExpiryTime());
            $this->assertNotEquals(ArchiveStatus::EXPIRED, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试查找已过期需要更新状态的档案
     */
    public function testFindExpiredArchivesReturnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ARCHIVED, new \DateTimeImmutable('-1 week'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::EXPIRED, new \DateTimeImmutable('-1 month'));
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+1 day'));

        $results = $this->repository->findExpiredArchives();

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThan(new \DateTimeImmutable(), $archive->getExpiryTime());
            $this->assertNotEquals(ArchiveStatus::EXPIRED, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试按状态统计数量
     */
    public function testCountByStatusReturnsCorrectCount(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ARCHIVED);

        $activeCount = $this->repository->countByStatus(ArchiveStatus::ACTIVE);
        $archivedCount = $this->repository->countByStatus(ArchiveStatus::ARCHIVED);
        $expiredCount = $this->repository->countByStatus(ArchiveStatus::EXPIRED);

        $this->assertEquals(3, $activeCount);
        $this->assertEquals(1, $archivedCount);
        $this->assertEquals(0, $expiredCount);
    }

    /**
     * 测试获取格式分布
     */
    public function testGetFormatDistributionReturnsCorrectDistribution(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::JSON);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::JSON);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::XML);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::CSV);

        $distribution = $this->repository->getFormatDistribution();

        $this->assertCount(3, $distribution);

        $formatCounts = [];
        foreach ($distribution as $item) {
            $formatCounts[$item['format']] = (int) $item['count'];
        }

        $this->assertEquals(2, $formatCounts[ArchiveFormat::JSON->value]);
        $this->assertEquals(1, $formatCounts[ArchiveFormat::XML->value]);
        $this->assertEquals(1, $formatCounts[ArchiveFormat::CSV->value]);
    }

    /**
     * 测试空数据统计
     */
    public function testGetArchiveStatsWithNoDataReturnsZeros(): void
    {
        $stats = $this->repository->getArchiveStats();

        $this->assertEquals(0, $stats['totalArchives']);
        $this->assertEquals(0, $stats['activeCount']);
        $this->assertEquals(0, $stats['archivedCount']);
        $this->assertEquals(0, $stats['expiredCount']);
    }

    /**
     * 测试过期日期排序
     */
    public function testFindExpiredOrderedByExpiryDate(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-3 days'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 week'));

        $results = $this->repository->findExpired();

        $this->assertCount(3, $results);
        // 验证按过期日期升序排列
        $previousDate = null;
        foreach ($results as $archive) {
            if (null !== $previousDate) {
                $this->assertGreaterThanOrEqual($previousDate, $archive->getExpiryTime());
            }
            $previousDate = $archive->getExpiryTime();
        }
    }

    /**
     * 测试获取每月归档数量
     */
    public function testGetMonthlyArchiveCount(): void
    {
        $result = $this->repository->getMonthlyArchiveCount();

        $this->assertIsArray($result);
        // 结果为空数组，因为没有数据
        $this->assertEmpty($result);
    }

    /**
     * 测试获取每月归档数量（自定义月数）
     */
    public function testGetMonthlyArchiveCountWithCustomMonths(): void
    {
        $result = $this->repository->getMonthlyArchiveCount(6);

        $this->assertIsArray($result);
        // 结果为空数组，因为没有数据
        $this->assertEmpty($result);
    }

    // ===== 基础 CRUD 操作测试 =====

    public function testFindAllShouldReturnArrayOfEntities(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnArchive::class, $entity);
        }
    }

    public function testFindOneByWithNonExistingCriteriaShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['archivePath' => 'nonexistent_path_' . uniqid()]);
        $this->assertNull($result);
    }

    public function testFindByNullValueShouldReturnEntitiesWithNullField(): void
    {
        $result = $this->repository->findBy(['archivePath' => null], null, 5);
        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnArchive::class, $entity);
            $this->assertNull($entity->getArchivePath());
        }
    }

    // ===== 测试 find 方法 =====

    public function testFindShouldReturnEntityWhenExists(): void
    {
        $archive = $this->createTestArchive();

        $result = $this->repository->find($archive->getId());

        $this->assertInstanceOf(LearnArchive::class, $result);
        $this->assertEquals($archive->getId(), $result->getId());
    }

    public function testFindShouldReturnNullWhenNotExists(): void
    {
        $result = $this->repository->find('999999999999999999');

        $this->assertNull($result);
    }

    // ===== 测试 save 和 remove 方法 =====

    public function testSaveShouldPersistEntity(): void
    {
        $archive = new LearnArchive();
        $archive->setUserId('test_user');
        $archive->setCourse($this->createOrGetTestCourse('test_course'));
        $archive->setArchiveStatus(ArchiveStatus::ACTIVE);
        $archive->setFileSize(1024);
        $archive->setChecksum('test_checksum');
        $archive->setMetadata(['test' => 'data']);

        $this->repository->save($archive);

        $this->assertNotNull($archive->getId());

        // 验证能从数据库中查找到
        $found = $this->repository->find($archive->getId());
        $this->assertNotNull($found);
        $this->assertEquals('test_user', $found->getUserId());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        $archive = $this->createTestArchive();
        $id = $archive->getId();

        $this->repository->remove($archive);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testSaveWithoutFlushShouldNotPersistImmediately(): void
    {
        $archive = new LearnArchive();
        $archive->setUserId('test_user_no_flush');
        $archive->setCourse($this->createOrGetTestCourse('test_course_no_flush'));
        $archive->setArchiveStatus(ArchiveStatus::ACTIVE);
        $archive->setFileSize(2048);
        $archive->setChecksum('test_checksum_no_flush');
        $archive->setMetadata(['test' => 'no_flush']);

        $this->repository->save($archive, false);

        // 清除一级缓存
        self::getEntityManager()->clear();

        // 不应该在数据库中找到
        $found = $this->repository->find($archive->getId());
        $this->assertNull($found);

        // 手动flush后应该能找到
        self::getEntityManager()->flush();
        $found = $this->repository->find($archive->getId());
        $this->assertNotNull($found);
    }

    // ===== 测试 count 方法 =====

    public function testCountShouldReturnCorrectNumber(): void
    {
        $initialCount = $this->repository->count([]);

        $this->createTestArchive('user1', 'course1');
        $this->createTestArchive('user2', 'course2');

        $finalCount = $this->repository->count([]);

        $this->assertEquals($initialCount + 2, $finalCount);
    }

    public function testCountWithCriteriaShouldReturnFilteredNumber(): void
    {
        $this->createTestArchive('user1', 'course1', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user2', 'course2', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user3', 'course3', ArchiveStatus::ACTIVE);

        $activeCount = $this->repository->count(['archiveStatus' => ArchiveStatus::ACTIVE]);
        $archivedCount = $this->repository->count(['archiveStatus' => ArchiveStatus::ARCHIVED]);

        $this->assertEquals(2, $activeCount);
        $this->assertEquals(1, $archivedCount);
    }

    // ===== 测试边界情况 =====

    public function testFindByWithEmptyCriteriaShouldReturnAllEntities(): void
    {
        $this->createTestArchive('user1', 'course1');
        $this->createTestArchive('user2', 'course2');

        $result = $this->repository->findBy([]);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnArchive::class, $entity);
        }
    }

    public function testFindByWithLimitShouldRespectLimit(): void
    {
        $this->createTestArchive('user1', 'course1');
        $this->createTestArchive('user2', 'course2');
        $this->createTestArchive('user3', 'course3');

        $result = $this->repository->findBy([], null, 2);

        $this->assertCount(2, $result);
    }

    public function testFindByWithOffsetShouldSkipRecords(): void
    {
        $archives = [];
        $archives[] = $this->createTestArchive('user1', 'course1');
        $archives[] = $this->createTestArchive('user2', 'course2');
        $archives[] = $this->createTestArchive('user3', 'course3');

        $result = $this->repository->findBy([], ['createTime' => 'ASC'], 2, 1);

        $this->assertCount(2, $result);
        // 验证第一个结果不是第一个创建的记录
        $this->assertNotEquals($archives[0]->getId(), $result[0]->getId());
    }

    // ===== 测试可空字段查询 =====

    public function testFindByNullArchivePathShouldReturnEntitiesWithNullPath(): void
    {
        $archive1 = $this->createTestArchive('user1', 'course1');
        $archive1->setArchivePath(null);
        self::getEntityManager()->flush();

        $archive2 = $this->createTestArchive('user2', 'course2');
        $archive2->setArchivePath('/path/to/archive');
        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['archivePath' => null]);

        $this->assertGreaterThanOrEqual(1, count($result));
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnArchive::class, $entity);
            $this->assertNull($entity->getArchivePath());
        }
    }

    public function testFindByNullLastVerificationTimeShouldReturnEntitiesWithNullTime(): void
    {
        $archive1 = $this->createTestArchive('user1', 'course1');
        $archive1->setLastVerifyTime(null);
        self::getEntityManager()->flush();

        $archive2 = $this->createTestArchive('user2', 'course2');
        $archive2->setLastVerifyTime(new \DateTimeImmutable());
        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['lastVerifyTime' => null]);

        $this->assertGreaterThanOrEqual(1, count($result));
        foreach ($result as $entity) {
            $this->assertInstanceOf(LearnArchive::class, $entity);
            $this->assertNull($entity->getLastVerifyTime());
        }
    }

    // ===== 测试排序功能 =====

    public function testFindByWithOrderByShouldReturnSortedResults(): void
    {
        $archive1 = $this->createTestArchive('user1', 'course1');
        $archive1->setTotalSessions(10);
        self::getEntityManager()->flush();

        $archive2 = $this->createTestArchive('user2', 'course2');
        $archive2->setTotalSessions(5);
        self::getEntityManager()->flush();

        $archive3 = $this->createTestArchive('user3', 'course3');
        $archive3->setTotalSessions(15);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], ['totalSessions' => 'DESC'], 3);

        $this->assertCount(3, $result);
        // 验证按totalSessions降序排列
        $this->assertGreaterThanOrEqual($result[1]->getTotalSessions(), $result[0]->getTotalSessions());
        $this->assertGreaterThanOrEqual($result[2]->getTotalSessions(), $result[1]->getTotalSessions());
    }

    // ===== 测试复杂查询条件 =====

    public function testFindByMultipleCriteriaShouldReturnMatchingEntities(): void
    {
        $this->createTestArchive('user1', 'course1', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user1', 'course2', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user2', 'course1', ArchiveStatus::ACTIVE);

        $result = $this->repository->findBy([
            'userId' => 'user1',
            'archiveStatus' => ArchiveStatus::ACTIVE,
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('user1', $result[0]->getUserId());
        $this->assertEquals(ArchiveStatus::ACTIVE, $result[0]->getArchiveStatus());
    }

    public function testFindWithNonExistingIdShouldReturnNull(): void
    {
        $result = $this->repository->find('999999999999999999');

        $this->assertNull($result);
    }

    // ===== 测试关联字段查询 =====

    public function testCountByAssociationCourseShouldReturnCorrectNumber(): void
    {
        $archive1 = $this->createTestArchive('user1', 'course1');
        $archive2 = $this->createTestArchive('user2', 'course1'); // 同一课程
        $archive3 = $this->createTestArchive('user3', 'course2'); // 不同课程

        $course = $archive1->getCourse();
        $count = $this->repository->count(['course' => $course]);

        $this->assertEquals(2, $count);
    }

    public function testFindOneByAssociationCourseShouldReturnMatchingEntity(): void
    {
        $archive1 = $this->createTestArchive('user1', 'course1');
        $archive2 = $this->createTestArchive('user2', 'course2');

        $course = $archive1->getCourse();
        $result = $this->repository->findOneBy(['course' => $course]);

        $this->assertNotNull($result);
        $this->assertEquals($archive1->getId(), $result->getId());
    }

    // ===== 测试可空字段的 NULL 查询 =====

    // ===== 测试实体完整性 =====

    public function testCreatedEntityShouldHaveCorrectDefaultValues(): void
    {
        $archive = $this->createTestArchive();

        $this->assertNotNull($archive->getId());
        $this->assertNotNull($archive->getCreateTime());
        $this->assertNotNull($archive->getUpdateTime());
        $this->assertNotNull($archive->getArchiveTime());
        $this->assertNotNull($archive->getExpiryTime());
        $this->assertEquals(ArchiveStatus::ACTIVE, $archive->getArchiveStatus());
        $this->assertEquals(ArchiveFormat::JSON, $archive->getArchiveFormat());
    }

    protected function createNewEntity(): object
    {
        return $this->createTestArchive();
    }

    /** @return ServiceEntityRepository<LearnArchive> */
/** @return ServiceEntityRepository<LearnArchive> */
/** @return ServiceEntityRepository<LearnArchive> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
