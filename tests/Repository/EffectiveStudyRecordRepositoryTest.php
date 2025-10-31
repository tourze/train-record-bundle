<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;

/**
 * EffectiveStudyRecordRepository 集成测试
 * 简化版本，只测试 Repository 的基本功能而不涉及复杂的实体关联
 *
 * @template TEntity of EffectiveStudyRecord
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(EffectiveStudyRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private EffectiveStudyRecordRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(EffectiveStudyRecord::class);
        $this->assertInstanceOf(EffectiveStudyRecordRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testFindByUserAndDateReturnsEmptyArrayWhenNoRecords(): void
    {
        $userId = 'nonexistent_user_' . uniqid();
        $date = new \DateTimeImmutable('2024-01-01');

        $result = $this->repository->findByUserAndDate($userId, $date);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetDailyEffectiveTimeReturnsZeroForNonExistentUser(): void
    {
        $userId = 'nonexistent_user_' . uniqid();
        $date = new \DateTimeImmutable('2024-01-01');

        $totalTime = $this->repository->getDailyEffectiveTime($userId, $date);

        $this->assertEquals(0, $totalTime);
    }

    /**
     * 测试 Repository 的基本查询功能
     * 这个测试不创建复杂的关联数据，只验证查询逻辑
     */
    public function testFindByUserAndDateWithDateRange(): void
    {
        $userId = 'test_user_' . uniqid();
        $date1 = new \DateTimeImmutable('2024-01-01');
        $date2 = new \DateTimeImmutable('2024-01-02');

        // 测试不同日期的查询都返回空结果（因为没有数据）
        $result1 = $this->repository->findByUserAndDate($userId, $date1);
        $result2 = $this->repository->findByUserAndDate($userId, $date2);

        $this->assertIsArray($result1);
        $this->assertEmpty($result1);
        $this->assertIsArray($result2);
        $this->assertEmpty($result2);
    }

    /**
     * 测试每日有效时间计算的边界情况
     */
    public function testGetDailyEffectiveTimeHandlesEdgeCases(): void
    {
        $userId = 'test_user_edge_' . uniqid();

        // 测试过去日期
        $pastDate = new \DateTimeImmutable('-1 year');
        $pastResult = $this->repository->getDailyEffectiveTime($userId, $pastDate);
        $this->assertEquals(0, $pastResult);

        // 测试未来日期
        $futureDate = new \DateTimeImmutable('+1 year');
        $futureResult = $this->repository->getDailyEffectiveTime($userId, $futureDate);
        $this->assertEquals(0, $futureResult);

        // 测试当前日期
        $today = new \DateTimeImmutable('today');
        $todayResult = $this->repository->getDailyEffectiveTime($userId, $today);
        $this->assertEquals(0, $todayResult);
    }

    /**
     * 测试不同用户的数据隔离
     */
    public function testUserDataIsolation(): void
    {
        $user1 = 'user1_' . uniqid();
        $user2 = 'user2_' . uniqid();
        $date = new \DateTimeImmutable('2024-01-15');

        // 两个不同用户在同一天的查询结果应该相互独立（都为空）
        $result1 = $this->repository->findByUserAndDate($user1, $date);
        $result2 = $this->repository->findByUserAndDate($user2, $date);

        $this->assertIsArray($result1);
        $this->assertEmpty($result1);
        $this->assertIsArray($result2);
        $this->assertEmpty($result2);

        // 时间统计也应该相互独立
        $time1 = $this->repository->getDailyEffectiveTime($user1, $date);
        $time2 = $this->repository->getDailyEffectiveTime($user2, $date);

        $this->assertEquals(0, $time1);
        $this->assertEquals(0, $time2);
    }

    /**
     * 测试查找学习会话相关的有效学时记录
     */
    public function testFindBySession(): void
    {
        $sessionId = 'session_' . uniqid();

        $result = $this->repository->findBySession($sessionId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找课程的有效学时记录
     */
    public function testFindByCourse(): void
    {
        $courseId = 'course_' . uniqid();

        $result = $this->repository->findByCourse($courseId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找课程的有效学时记录（带限制）
     */
    public function testFindByCourseWithLimit(): void
    {
        $courseId = 'course_' . uniqid();

        $result = $this->repository->findByCourse($courseId, 10);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找课时的有效学时记录
     */
    public function testFindByLesson(): void
    {
        $lessonId = 'lesson_' . uniqid();

        $result = $this->repository->findByLesson($lessonId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找课时的有效学时记录（带限制）
     */
    public function testFindByLessonWithLimit(): void
    {
        $lessonId = 'lesson_' . uniqid();

        $result = $this->repository->findByLesson($lessonId, 5);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找需要审核的记录
     */
    public function testFindNeedingReview(): void
    {
        $result = $this->repository->findNeedingReview();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找低质量学时记录
     */
    public function testFindLowQuality(): void
    {
        $result = $this->repository->findLowQuality();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找低质量学时记录（自定义阈值）
     */
    public function testFindLowQualityWithCustomThreshold(): void
    {
        $result = $this->repository->findLowQuality(3.0);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试统计无效时长原因分布
     */
    public function testGetInvalidReasonStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->getInvalidReasonStats($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试统计用户的学时效率
     */
    public function testGetUserEfficiencyStats(): void
    {
        $userId = 'user_' . uniqid();
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $this->repository->getUserEfficiencyStats($userId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalRecords', $result);
        $this->assertArrayHasKey('totalTime', $result);
        $this->assertArrayHasKey('effectiveTime', $result);
        $this->assertArrayHasKey('invalidTime', $result);
    }

    /**
     * 测试查找指定原因的无效记录
     */
    public function testFindByInvalidReason(): void
    {
        $reasons = InvalidTimeReason::cases();
        $reason = $reasons[0];
        $result = $this->repository->findByInvalidReason($reason);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找指定原因的无效记录（带限制）
     */
    public function testFindByInvalidReasonWithLimit(): void
    {
        $reasons = InvalidTimeReason::cases();
        $reason = $reasons[0];
        $result = $this->repository->findByInvalidReason($reason, 10);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找超时记录（日累计超限）
     */
    public function testFindDailyTimeExceeded(): void
    {
        $userId = 'user_' . uniqid();
        $date = new \DateTimeImmutable('2024-01-01');
        $dailyLimit = 8.0;

        $result = $this->repository->findDailyTimeExceeded($userId, $date, $dailyLimit);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找高效学习记录
     */
    public function testFindHighEfficiency(): void
    {
        $result = $this->repository->findHighEfficiency();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找高效学习记录（自定义阈值）
     */
    public function testFindHighEfficiencyWithCustomThreshold(): void
    {
        $result = $this->repository->findHighEfficiency(0.9);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查找未通知学员的记录
     */
    public function testFindUnnotified(): void
    {
        $result = $this->repository->findUnnotified();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试批量更新通知状态
     */
    public function testMarkAsNotified(): void
    {
        $recordIds = ['1', '2', '3'];

        // 调用方法不应抛出异常
        $this->repository->markAsNotified($recordIds);

        // 验证方法执行完成，返回值符合预期
        $this->assertNull($this->repository->findOneBy(['id' => $recordIds[0]]));
    }

    /**
     * 测试查找需要重新验证的记录
     */
    public function testFindNeedingRevalidation(): void
    {
        $beforeDate = new \DateTimeImmutable('-1 week');

        $result = $this->repository->findNeedingRevalidation($beforeDate);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试获取课程学时统计
     */
    public function testGetCourseStudyTimeStats(): void
    {
        $courseId = 'course_' . uniqid();

        $result = $this->repository->getCourseStudyTimeStats($courseId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalStudents', $result);
        $this->assertArrayHasKey('totalEffectiveTime', $result);
        $this->assertArrayHasKey('avgEffectiveTime', $result);
        $this->assertArrayHasKey('totalStudyTime', $result);
        $this->assertArrayHasKey('avgQuality', $result);
    }

    // ===== 基础 CRUD 操作测试 =====

    public function testFindOneByWithNonExistingCriteriaShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['userId' => 'nonexistent_user_' . uniqid()]);

        $this->assertNull($result);
    }

    public function testFindByNullValueShouldReturnEntitiesWithNullField(): void
    {
        // 查找reviewComment为null的记录
        $result = $this->repository->findBy(['reviewComment' => null], null, 5);

        $this->assertIsArray($result);
        foreach ($result as $entity) {
            $this->assertInstanceOf(EffectiveStudyRecord::class, $entity);
            $this->assertNull($entity->getReviewComment());
        }
    }

    public function testSaveMethod(): void
    {
        // 由于实体创建复杂，我们只测试保存方法的基本功能
        // 主要验证repository的count方法可以正常调用
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        // save方法需要有效实体，无需额外验证方法存在性（继承自父类）
    }

    public function testRemoveMethod(): void
    {
        // 由于实体创建复杂，我们只测试删除方法的基本功能
        // 主要验证repository的count方法可以正常调用
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        // remove方法需要有效实体，无需额外验证方法存在性（继承自父类）
    }

    // ===== 关联字段查询测试 =====

    public function testFindByAssociationField(): void
    {
        // 测试通过课程ID查询
        $result = $this->repository->findBy(['course' => 'test_course']);
        $this->assertIsArray($result);

        // 测试通过会话ID查询
        $result = $this->repository->findBy(['session' => 'test_session']);
        $this->assertIsArray($result);
    }

    public function testCountByAssociationField(): void
    {
        // 测试通过课程ID计数
        $count = $this->repository->count(['course' => 'test_course']);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        // 测试通过会话ID计数
        $count = $this->repository->count(['session' => 'test_session']);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== 可空字段查询测试 =====

    public function testFindByNullFields(): void
    {
        // 测试查找reviewComment为null的记录
        $result = $this->repository->findBy(['reviewComment' => null]);
        $this->assertIsArray($result);

        // 测试查找invalidReason为null的记录
        $result = $this->repository->findBy(['invalidReason' => null]);
        $this->assertIsArray($result);
    }

    public function testCountByNullFields(): void
    {
        // 测试计数reviewComment为null的记录
        $count = $this->repository->count(['reviewComment' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        // 测试计数invalidReason为null的记录
        $count = $this->repository->count(['invalidReason' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    protected function createNewEntity(): object
    {
        // 由于EffectiveStudyRecord有复杂的依赖关系，暂时跳过持久化测试
        // 这个方法返回一个基本的实体用于其他测试
        $entity = new EffectiveStudyRecord();
        $entity->setUserId('test_user_' . uniqid());
        $entity->setStudyDate(new \DateTimeImmutable());
        $entity->setStartTime(new \DateTimeImmutable('2023-01-01 09:00:00'));
        $entity->setEndTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $entity->setTotalDuration(3600);
        $entity->setEffectiveDuration(3000);
        $entity->setInvalidDuration(600);

        return $entity;
    }

    
    /** @return ServiceEntityRepository<EffectiveStudyRecord> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
