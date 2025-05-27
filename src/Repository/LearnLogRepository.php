<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\LearnLog;

/**
 * @method LearnLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearnLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearnLog[]    findAll()
 * @method LearnLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearnLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearnLog::class);
    }
}
