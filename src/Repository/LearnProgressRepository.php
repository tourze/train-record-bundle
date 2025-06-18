<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnProgress;

/**
 * @method LearnProgress|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnProgress|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnProgress[]    findAll()
 * @method LearnProgress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnProgress::class);
    }

    /**
     * 查找用户在课程中的学习进度
     */
    public function findByUserAndCourse(string $userId, string $courseId): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('lp.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找用户在特定课时的学习进度
     */
    public function findByUserAndLesson(string $userId, string $lessonId): ?LearnProgress
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.lesson = :lessonId')
            ->setParameter('userId', $userId)
            ->setParameter('lessonId', $lessonId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找已完成的学习进度
     */
    public function findCompletedByUser(string $userId): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.isCompleted = true')
            ->setParameter('userId', $userId)
            ->orderBy('lp.updateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计用户的课程完成情况
     */
    public function getCourseCompletionStats(string $userId, string $courseId): array
    {
        return $this->createQueryBuilder('lp')
            ->select('COUNT(lp.id) as totalLessons, SUM(CASE WHEN lp.isCompleted = true THEN 1 ELSE 0 END) as completedLessons, AVG(lp.progress) as avgProgress')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * 查找需要同步的进度记录
     */
    public function findNeedingSync(\DateTimeInterface $lastSyncTime): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.lastUpdateTime > :lastSyncTime')
            ->setParameter('lastSyncTime', $lastSyncTime)
            ->orderBy('lp.lastUpdateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找低质量学习记录
     */
    public function findLowQualityProgress(float $qualityThreshold = 5.0): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.qualityScore < :threshold')
            ->andWhere('lp.qualityScore IS NOT NULL')
            ->setParameter('threshold', $qualityThreshold)
            ->orderBy('lp.qualityScore', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计学习效率分布
     */
    public function getLearningEfficiencyStats(): array
    {
        return $this->createQueryBuilder('lp')
            ->select('
                COUNT(lp.id) as totalRecords,
                AVG(lp.effectiveDuration / lp.watchedDuration) as avgEfficiency,
                MIN(lp.effectiveDuration / lp.watchedDuration) as minEfficiency,
                MAX(lp.effectiveDuration / lp.watchedDuration) as maxEfficiency
            ')
            ->andWhere('lp.watchedDuration > 0')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * 查找最近更新的进度记录
     */
    public function findRecentlyUpdated(int $limit = 50): array
    {
        return $this->createQueryBuilder('lp')
            ->orderBy('lp.lastUpdateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 批量更新有效学习时长
     */
    public function batchUpdateEffectiveDuration(array $progressIds, array $effectiveDurations): void
    {
        foreach ($progressIds as $index => $progressId) {
            $effectiveDuration = $effectiveDurations[$index];
            
            $this->createQueryBuilder('lp')
                ->update()
                ->set('lp.effectiveDuration', ':effectiveDuration')
                ->andWhere('lp.id = :progressId')
                ->setParameter('effectiveDuration', $effectiveDuration)
                ->setParameter('progressId', $progressId)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * 更新学习进度
     * 
     * @param LearnProgress $progress
     * @param bool $flush
     */
    public function updateProgress(LearnProgress $progress, bool $flush = true): void
    {
        $this->getEntityManager()->persist($progress);
        
        if ((bool) $flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据课程查找学习进度
     */
    public function findByCourse(string $courseId): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据用户查找学习进度
     */
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据用户和日期范围查找学习进度
     */
    public function findByUserAndDateRange(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.userId = :userId')
            ->andWhere('lp.createTime >= :startDate')
            ->andWhere('lp.createTime <= :endDate')
            ->setParameter('userId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lp.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 