<?php

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainRecordBundle\Entity\FaceDetect;

/**
 * @method FaceDetect|null find($id, $lockMode = null, $lockVersion = null)
 * @method FaceDetect|null findOneBy(array $criteria, array $orderBy = null)
 * @method FaceDetect[]    findAll()
 * @method FaceDetect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FaceDetectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaceDetect::class);
    }

    /**
     * 查找会话的人脸检测记录
     */
    public function findBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已验证的人脸检测记录
     */
    public function findVerifiedBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.isVerified = :verified')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('verified', true)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计会话的人脸检测次数
     */
    public function countBySession(string $sessionId): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 统计会话的已验证人脸检测次数
     */
    public function countVerifiedBySession(string $sessionId): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.isVerified = :verified')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 查找低相似度的人脸检测记录
     */
    public function findLowSimilarityBySession(string $sessionId, string $threshold = '0.80'): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.similarity < :threshold OR f.similarity IS NULL')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('threshold', $threshold)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}