<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * @method LearnSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnSession[]    findAll()
 * @method LearnSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnSession::class);
    }

    /**
     * 查找学员的活跃学习会话
     * 
     * @param mixed $student 学员实体
     * @return LearnSession[]
     */
    public function findActiveSessionsByStudent($student): array
    {
        return $this->createQueryBuilder('ls')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('finished', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找学员在其他课程的活跃会话
     * 
     * @param mixed $student 学员实体
     * @param string $currentLessonId 当前课时ID
     * @return LearnSession[]
     */
    public function findOtherActiveSessionsByStudent($student, string $currentLessonId): array
    {
        return $this->createQueryBuilder('ls')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->andWhere('ls.finished = :finished')
            ->andWhere('ls.lesson != :currentLesson')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('finished', false)
            ->setParameter('currentLesson', $currentLessonId)
            ->getQuery()
            ->getResult();
    }

    /**
     * 将学员的所有活跃会话设置为非活跃
     * 
     * @param mixed $student 学员实体
     * @return int 更新的记录数
     */
    public function deactivateAllActiveSessionsByStudent($student): int
    {
        return $this->createQueryBuilder('ls')
            ->update()
            ->set('ls.active', ':inactive')
            ->where('ls.student = :student')
            ->andWhere('ls.active = :active')
            ->setParameter('student', $student)
            ->setParameter('active', true)
            ->setParameter('inactive', false)
            ->getQuery()
            ->execute();
    }

    /**
     * 保存学习会话
     */
    public function save(LearnSession $session, bool $flush = true): void
    {
        $this->getEntityManager()->persist($session);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
