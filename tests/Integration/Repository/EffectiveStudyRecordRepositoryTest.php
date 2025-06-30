<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Tests\Integration\TrainRecordTestCase;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;

/**
 * EffectiveStudyRecordRepository 集成测试
 */
class EffectiveStudyRecordRepositoryTest extends TrainRecordTestCase
{
    private EntityManagerInterface $entityManager;
    private EffectiveStudyRecordRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        $repository = $this->entityManager->getRepository(EffectiveStudyRecord::class);
        $this->assertInstanceOf(EffectiveStudyRecordRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . EffectiveStudyRecord::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试记录
     */
    private function createTestRecord(
        string $userId = 'user001',
        ?\DateTimeInterface $studyDate = null,
        float $effectiveDuration = 3600,
        float $totalDuration = 4000,
        StudyTimeStatus $status = StudyTimeStatus::VALID,
        ?InvalidTimeReason $invalidReason = null
    ): EffectiveStudyRecord {
        $record = new EffectiveStudyRecord();
        $record->setUserId($userId);
        $record->setStudyDate($studyDate instanceof \DateTimeImmutable ? $studyDate : new \DateTimeImmutable($studyDate?->format('Y-m-d H:i:s') ?? 'now'));
        $record->setStartTime(new \DateTimeImmutable());
        $record->setEndTime(new \DateTimeImmutable('+1 hour'));
        $record->setEffectiveDuration($effectiveDuration);
        $record->setTotalDuration($totalDuration);
        $record->setInvalidDuration($totalDuration - $effectiveDuration);
        $record->setStatus($status);
        $record->setInvalidReason($invalidReason);
        $record->setSession($this->createMockSession('session001'));
        $record->setCourse($this->createMockCourse('course001'));
        $record->setLesson($this->createMockLesson('lesson001'));
        $record->setQualityScore(8.5);
        $record->setFocusScore(9.0);
        $record->setInteractionScore(7.5);
        $record->setContinuityScore(8.0);
        $record->setIncludeInDailyTotal(true);
        $record->setStudentNotified(false);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * 创建模拟学习会话
     */
    private function createMockSession(string $sessionId): LearnSession
    {
        $session = new LearnSession();
        // LearnSession 实体使用复杂的关联，我们在测试中直接使用反射设置 ID
        $reflection = new \ReflectionClass($session);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($session, $sessionId);
        }
        return $session;
    }

    /**
     * 创建模拟课程
     */
    private function createMockCourse(string $courseId): Course
    {
        $course = new Course();
        // 假设 Course 有 setId 方法
        $reflection = new \ReflectionClass($course);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($course, $courseId);
        }
        return $course;
    }

    /**
     * 创建模拟课时
     */
    private function createMockLesson(string $lessonId): Lesson
    {
        $lesson = new Lesson();
        // 假设 Lesson 有 setId 方法
        $reflection = new \ReflectionClass($lesson);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($lesson, $lessonId);
        }
        return $lesson;
    }

    /**
     * 测试根据用户和日期查找记录
     */
    public function test_findByUserAndDate_returnsCorrectRecords(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $this->createTestRecord('user001', $date);
        $this->createTestRecord('user001', $date);
        $this->createTestRecord('user001', new \DateTimeImmutable('2024-01-16'));
        $this->createTestRecord('user002', $date);

        $results = $this->repository->findByUserAndDate('user001', $date);

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('user001', $record->getUserId());
            $this->assertEquals($date->format('Y-m-d'), $record->getStudyDate()->format('Y-m-d'));
        }
    }

    /**
     * 测试计算日有效学时
     */
    public function test_getDailyEffectiveTime_returnsCorrectSum(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $this->createTestRecord('user001', $date, 3600, 4000, StudyTimeStatus::VALID);
        $this->createTestRecord('user001', $date, 1800, 2000, StudyTimeStatus::VALID);
        $this->createTestRecord('user001', $date, 900, 1000, StudyTimeStatus::INVALID);
        
        $totalTime = $this->repository->getDailyEffectiveTime('user001', $date);

        $this->assertEquals(5400.0, $totalTime); // 3600 + 1800，不包括无效的900
    }

    /**
     * 测试根据会话查找记录
     */
    public function test_findBySession_returnsCorrectRecords(): void
    {
        $record1 = new EffectiveStudyRecord();
        $record1->setUserId('user001');
        $record1->setStudyDate(new \DateTimeImmutable());
        $record1->setStartTime(new \DateTimeImmutable('09:00:00'));
        $record1->setEndTime(new \DateTimeImmutable('10:00:00'));
        $record1->setEffectiveDuration(3600);
        $record1->setTotalDuration(3600);
        $record1->setStatus(StudyTimeStatus::VALID);
        $record1->setSession($this->createMockSession('session001'));
        $record1->setCourse($this->createMockCourse('course001'));
        $record1->setLesson($this->createMockLesson('lesson001'));
        $this->entityManager->persist($record1);

        $record2 = new EffectiveStudyRecord();
        $record2->setUserId('user001');
        $record2->setStudyDate(new \DateTimeImmutable());
        $record2->setStartTime(new \DateTimeImmutable('10:00:00'));
        $record2->setEndTime(new \DateTimeImmutable('11:00:00'));
        $record2->setEffectiveDuration(3600);
        $record2->setTotalDuration(3600);
        $record2->setStatus(StudyTimeStatus::VALID);
        $record2->setSession($this->createMockSession('session001'));
        $record2->setCourse($this->createMockCourse('course001'));
        $record2->setLesson($this->createMockLesson('lesson002'));
        $this->entityManager->persist($record2);

        $record3 = new EffectiveStudyRecord();
        $record3->setUserId('user002');
        $record3->setStudyDate(new \DateTimeImmutable());
        $record3->setStartTime(new \DateTimeImmutable('09:00:00'));
        $record3->setEndTime(new \DateTimeImmutable('10:00:00'));
        $record3->setEffectiveDuration(3600);
        $record3->setTotalDuration(3600);
        $record3->setStatus(StudyTimeStatus::VALID);
        $record3->setSession($this->createMockSession('session002'));
        $record3->setCourse($this->createMockCourse('course001'));
        $record3->setLesson($this->createMockLesson('lesson001'));
        $this->entityManager->persist($record3);

        $this->entityManager->flush();

        $results = $this->repository->findBySession('session001');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('session001', $record->getSession());
        }
    }

    /**
     * 测试查找需要审核的记录
     */
    public function test_findNeedingReview_returnsCorrectRecords(): void
    {
        $this->createTestRecord('user001', null, 3600, 4000, StudyTimeStatus::PENDING);
        $this->createTestRecord('user002', null, 3600, 4000, StudyTimeStatus::REVIEWING);
        $this->createTestRecord('user003', null, 3600, 4000, StudyTimeStatus::PARTIAL);
        $this->createTestRecord('user004', null, 3600, 4000, StudyTimeStatus::VALID);

        $results = $this->repository->findNeedingReview();

        $this->assertCount(3, $results);
        $statuses = array_map(fn($r) => $r->getStatus(), $results);
        $this->assertContains(StudyTimeStatus::PENDING, $statuses);
        $this->assertContains(StudyTimeStatus::REVIEWING, $statuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $statuses);
        $this->assertNotContains(StudyTimeStatus::VALID, $statuses);
    }

    /**
     * 测试查找低质量记录
     */
    public function test_findLowQuality_returnsCorrectRecords(): void
    {
        $record1 = $this->createTestRecord('user001');
        $record1->setQualityScore(3.5);
        $this->entityManager->flush();

        $record2 = $this->createTestRecord('user002');
        $record2->setQualityScore(4.8);
        $this->entityManager->flush();

        $record3 = $this->createTestRecord('user003');
        $record3->setQualityScore(8.5);
        $this->entityManager->flush();

        $results = $this->repository->findLowQuality(5.0);

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertLessThan(5.0, $record->getQualityScore());
        }
    }

    /**
     * 测试无效原因统计
     */
    public function test_getInvalidReasonStats_returnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestRecord('user001', $startDate, 3000, 3600, StudyTimeStatus::PARTIAL, InvalidTimeReason::SUSPICIOUS_BEHAVIOR);
        $this->createTestRecord('user002', $startDate, 2700, 3600, StudyTimeStatus::PARTIAL, InvalidTimeReason::SUSPICIOUS_BEHAVIOR);
        $this->createTestRecord('user003', $startDate, 0, 3600, StudyTimeStatus::INVALID, InvalidTimeReason::NO_ACTIVITY_DETECTED);

        $stats = $this->repository->getInvalidReasonStats($startDate, $endDate);

        $this->assertCount(2, $stats);
        
        $reasonStats = [];
        foreach ($stats as $stat) {
            $reasonStats[$stat['invalidReason']->value] = $stat;
        }
        
        $this->assertEquals(2, $reasonStats[InvalidTimeReason::SUSPICIOUS_BEHAVIOR->value]['count']);
        $this->assertEquals(1800.0, $reasonStats[InvalidTimeReason::SUSPICIOUS_BEHAVIOR->value]['totalInvalidTime']); // 600 + 900
        $this->assertEquals(1, $reasonStats[InvalidTimeReason::NO_ACTIVITY_DETECTED->value]['count']);
        $this->assertEquals(3600.0, $reasonStats[InvalidTimeReason::NO_ACTIVITY_DETECTED->value]['totalInvalidTime']);
    }

    /**
     * 测试用户效率统计
     */
    public function test_getUserEfficiencyStats_returnsCorrectStats(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        
        $this->createTestRecord('user001', $startDate, 3600, 4000);
        $this->createTestRecord('user001', new \DateTimeImmutable('2024-01-15'), 1800, 2000);

        $stats = $this->repository->getUserEfficiencyStats('user001', $startDate, $endDate);

        $this->assertEquals(2, $stats['totalRecords']);
        $this->assertEquals(6000.0, $stats['totalTime']); // 4000 + 2000
        $this->assertEquals(5400.0, $stats['effectiveTime']); // 3600 + 1800
        $this->assertEquals(600.0, $stats['invalidTime']); // 400 + 200
        $this->assertEquals(8.5, $stats['avgQuality']);
    }

    /**
     * 测试查找未通知记录
     */
    public function test_findUnnotified_returnsCorrectRecords(): void
    {
        $record1 = $this->createTestRecord('user001', null, 3600, 4000, StudyTimeStatus::VALID);
        $record1->setStudentNotified(false);
        $this->entityManager->flush();

        $record2 = $this->createTestRecord('user002', null, 3600, 4000, StudyTimeStatus::VALID);
        $record2->setStudentNotified(true);
        $this->entityManager->flush();

        $results = $this->repository->findUnnotified();

        $this->assertCount(1, $results);
        $this->assertEquals('user001', $results[0]->getUserId());
        $this->assertFalse($results[0]->isStudentNotified());
    }

    /**
     * 测试标记为已通知
     */
    public function test_markAsNotified_updatesRecords(): void
    {
        $record1 = $this->createTestRecord('user001');
        $record2 = $this->createTestRecord('user002');
        $record3 = $this->createTestRecord('user003');

        $this->repository->markAsNotified([$record1->getId(), $record2->getId()]);
        $this->entityManager->clear();

        $updatedRecord1 = $this->repository->find($record1->getId());
        $updatedRecord2 = $this->repository->find($record2->getId());
        $updatedRecord3 = $this->repository->find($record3->getId());

        $this->assertTrue($updatedRecord1->isStudentNotified());
        $this->assertTrue($updatedRecord2->isStudentNotified());
        $this->assertFalse($updatedRecord3->isStudentNotified());
    }

    /**
     * 测试查找高效率记录
     */
    public function test_findHighEfficiency_returnsCorrectRecords(): void
    {
        $this->createTestRecord('user001', null, 900, 1000); // 90% 效率
        $this->createTestRecord('user002', null, 700, 1000); // 70% 效率
        $this->createTestRecord('user003', null, 850, 1000); // 85% 效率

        $results = $this->repository->findHighEfficiency(0.8);

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $efficiency = $record->getEffectiveDuration() / $record->getTotalDuration();
            $this->assertGreaterThanOrEqual(0.8, $efficiency);
        }
    }

    /**
     * 测试课程学时统计
     */
    public function test_getCourseStudyTimeStats_returnsCorrectStats(): void
    {
        $record1 = new EffectiveStudyRecord();
        $record1->setUserId('user001');
        $record1->setStudyDate(new \DateTimeImmutable());
        $record1->setStartTime(new \DateTimeImmutable());
        $record1->setEndTime(new \DateTimeImmutable('+1 hour'));
        $record1->setEffectiveDuration(3600);
        $record1->setTotalDuration(3600);
        $record1->setStatus(StudyTimeStatus::VALID);
        $record1->setSession($this->createMockSession('session001'));
        $record1->setCourse($this->createMockCourse('course001'));
        $record1->setLesson($this->createMockLesson('lesson001'));
        $record1->setQualityScore(8.0);
        $this->entityManager->persist($record1);

        $record2 = new EffectiveStudyRecord();
        $record2->setUserId('user002');
        $record2->setStudyDate(new \DateTimeImmutable());
        $record2->setStartTime(new \DateTimeImmutable());
        $record2->setEndTime(new \DateTimeImmutable('+30 minutes'));
        $record2->setEffectiveDuration(1800);
        $record2->setTotalDuration(1800);
        $record2->setStatus(StudyTimeStatus::VALID);
        $record2->setSession($this->createMockSession('session002'));
        $record2->setCourse($this->createMockCourse('course001'));
        $record2->setLesson($this->createMockLesson('lesson001'));
        $record2->setQualityScore(9.0);
        $this->entityManager->persist($record2);

        $this->entityManager->flush();

        $stats = $this->repository->getCourseStudyTimeStats('course001');

        $this->assertEquals(2, $stats['totalStudents']);
        $this->assertEquals(5400.0, $stats['totalEffectiveTime']); // 3600 + 1800
        $this->assertEquals(2700.0, $stats['avgEffectiveTime']); // 5400 / 2
        $this->assertEquals(5400.0, $stats['totalStudyTime']);
        $this->assertEquals(8.5, $stats['avgQuality']); // (8 + 9) / 2
    }
}