<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainRecordBundle\Entity\FaceDetect;

/**
 * @extends ServiceEntityRepository<FaceDetect>
 */
#[AsRepository(entityClass: FaceDetect::class)]
class FaceDetectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaceDetect::class);
    }

    /**
     * 查找会话的人脸检测记录
     * @return array<FaceDetect>
     */
    public function findBySession(string $sessionId): array
    {
        /** @var array<FaceDetect> */
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已验证的人脸检测记录
     * @return array<FaceDetect>
     */
    public function findVerifiedBySession(string $sessionId): array
    {
        /** @var array<FaceDetect> */
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.isVerified = :verified')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('verified', true)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计会话的人脸检测次数
     */
    public function countBySession(string $sessionId): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.session = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 统计会话的已验证人脸检测次数
     */
    public function countVerifiedBySession(string $sessionId): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.isVerified = :verified')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 查找低相似度的人脸检测记录
     * @return array<FaceDetect>
     */
    public function findLowSimilarityBySession(string $sessionId, string $threshold = '0.80'): array
    {
        /** @var array<FaceDetect> */
        return $this->createQueryBuilder('f')
            ->andWhere('f.session = :sessionId')
            ->andWhere('f.similarity < :threshold OR f.similarity IS NULL')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('threshold', $threshold)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存实体
     */
    public function save(FaceDetect $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(FaceDetect $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
