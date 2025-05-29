<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * 有效学时记录Repository
 * 提供有效学时查询、统计和分析功能
 *
 * @method EffectiveStudyRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method EffectiveStudyRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method EffectiveStudyRecord[]    findAll()
 * @method EffectiveStudyRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EffectiveStudyRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EffectiveStudyRecord::class);
    }

    /**
     * 查找用户在指定日期的有效学时记录
     */
    public function findByUserAndDate(string $userId, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.userId = :userId')
            ->andWhere('esr.studyDate = :date')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date)
            ->orderBy('esr.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 计算用户在指定日期的总有效学时
     */
    public function getDailyEffectiveTime(string $userId, \DateTimeInterface $date): float
    {
        $result = $this->createQueryBuilder('esr')
            ->select('SUM(esr.effectiveDuration)')
            ->andWhere('esr.userId = :userId')
            ->andWhere('esr.studyDate = :date')
            ->andWhere('esr.status = :validStatus')
            ->andWhere('esr.includeInDailyTotal = true')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date)
            ->setParameter('validStatus', StudyTimeStatus::VALID)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * 查找学习会话相关的有效学时记录
     */
    public function findBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('esr.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找课程的有效学时记录
     */
    public function findByCourse(string $courseId, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('esr')
            ->andWhere('esr.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('esr.startTime', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 查找课时的有效学时记录
     */
    public function findByLesson(string $lessonId, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('esr')
            ->andWhere('esr.lesson = :lessonId')
            ->setParameter('lessonId', $lessonId)
            ->orderBy('esr.startTime', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 查找需要审核的记录
     */
    public function findNeedingReview(): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.status IN (:reviewStatuses)')
            ->setParameter('reviewStatuses', [
                StudyTimeStatus::PENDING,
                StudyTimeStatus::REVIEWING,
                StudyTimeStatus::PARTIAL
            ])
            ->orderBy('esr.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找低质量学时记录
     */
    public function findLowQuality(float $qualityThreshold = 5.0): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.qualityScore < :threshold')
            ->andWhere('esr.qualityScore IS NOT NULL')
            ->setParameter('threshold', $qualityThreshold)
            ->orderBy('esr.qualityScore', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计无效时长原因分布
     */
    public function getInvalidReasonStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('esr')
            ->select('esr.invalidReason, COUNT(esr.id) as count, SUM(esr.invalidDuration) as totalInvalidTime')
            ->andWhere('esr.studyDate BETWEEN :startDate AND :endDate')
            ->andWhere('esr.invalidReason IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('esr.invalidReason')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计用户的学时效率
     */
    public function getUserEfficiencyStats(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('esr')
            ->select('
                COUNT(esr.id) as totalRecords,
                SUM(esr.totalDuration) as totalTime,
                SUM(esr.effectiveDuration) as effectiveTime,
                SUM(esr.invalidDuration) as invalidTime,
                AVG(esr.qualityScore) as avgQuality,
                AVG(esr.focusScore) as avgFocus,
                AVG(esr.interactionScore) as avgInteraction,
                AVG(esr.continuityScore) as avgContinuity
            ')
            ->andWhere('esr.userId = :userId')
            ->andWhere('esr.studyDate BETWEEN :startDate AND :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * 查找指定原因的无效记录
     */
    public function findByInvalidReason(InvalidTimeReason $reason, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('esr')
            ->andWhere('esr.invalidReason = :reason')
            ->setParameter('reason', $reason)
            ->orderBy('esr.createTime', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 查找超时记录（日累计超限）
     */
    public function findDailyTimeExceeded(string $userId, \DateTimeInterface $date, float $dailyLimit): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.userId = :userId')
            ->andWhere('esr.studyDate = :date')
            ->andWhere('esr.invalidReason = :reason')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date)
            ->setParameter('reason', InvalidTimeReason::DAILY_LIMIT_EXCEEDED)
            ->orderBy('esr.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找高效学习记录
     */
    public function findHighEfficiency(float $efficiencyThreshold = 0.8): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('(esr.effectiveDuration / esr.totalDuration) >= :threshold')
            ->andWhere('esr.totalDuration > 0')
            ->setParameter('threshold', $efficiencyThreshold)
            ->orderBy('esr.effectiveDuration', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找未通知学员的记录
     */
    public function findUnnotified(): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.studentNotified = false')
            ->andWhere('esr.status IN (:finalStatuses)')
            ->setParameter('finalStatuses', [
                StudyTimeStatus::VALID,
                StudyTimeStatus::INVALID,
                StudyTimeStatus::REJECTED,
                StudyTimeStatus::APPROVED
            ])
            ->orderBy('esr.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 批量更新通知状态
     */
    public function markAsNotified(array $recordIds): void
    {
        $this->createQueryBuilder('esr')
            ->update()
            ->set('esr.studentNotified', true)
            ->andWhere('esr.id IN (:recordIds)')
            ->setParameter('recordIds', $recordIds)
            ->getQuery()
            ->execute();
    }

    /**
     * 查找需要重新验证的记录
     */
    public function findNeedingRevalidation(\DateTimeInterface $beforeDate): array
    {
        return $this->createQueryBuilder('esr')
            ->andWhere('esr.createTime < :beforeDate')
            ->andWhere('esr.status = :pendingStatus')
            ->setParameter('beforeDate', $beforeDate)
            ->setParameter('pendingStatus', StudyTimeStatus::PENDING)
            ->orderBy('esr.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取课程学时统计
     */
    public function getCourseStudyTimeStats(string $courseId): array
    {
        return $this->createQueryBuilder('esr')
            ->select('
                COUNT(DISTINCT esr.userId) as totalStudents,
                SUM(esr.effectiveDuration) as totalEffectiveTime,
                AVG(esr.effectiveDuration) as avgEffectiveTime,
                SUM(esr.totalDuration) as totalStudyTime,
                AVG(esr.qualityScore) as avgQuality
            ')
            ->andWhere('esr.course = :courseId')
            ->andWhere('esr.status = :validStatus')
            ->setParameter('courseId', $courseId)
            ->setParameter('validStatus', StudyTimeStatus::VALID)
            ->getQuery()
            ->getSingleResult();
    }
} 