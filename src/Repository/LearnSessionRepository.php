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
}
