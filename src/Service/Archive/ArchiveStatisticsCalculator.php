<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Archive;

use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;

/**
 * 档案统计计算器
 * 负责档案相关的统计计算
 */
class ArchiveStatisticsCalculator
{
    public function __construct(
        private readonly LearnArchiveRepository $archiveRepository,
    ) {
    }

    /**
     * 获取档案统计
     * @return array<string, mixed>
     */
    public function getArchiveStatistics(): array
    {
        return [
            'totalArchives' => $this->archiveRepository->countByStatus(ArchiveStatus::ACTIVE),
            'expiredArchives' => $this->archiveRepository->countByStatus(ArchiveStatus::EXPIRED),
            'archivedArchives' => $this->archiveRepository->countByStatus(ArchiveStatus::ARCHIVED),
            'totalStorageSize' => $this->calculateTotalStorageSize(),
            'formatDistribution' => $this->archiveRepository->getFormatDistribution(),
            'monthlyArchiveCount' => $this->archiveRepository->getMonthlyArchiveCount(),
        ];
    }

    /**
     * 计算总存储大小
     */
    private function calculateTotalStorageSize(): int
    {
        $archives = $this->archiveRepository->findAll();
        $totalSize = 0;

        foreach ($archives as $archive) {
            $archivePath = $archive->getArchivePath();
            if (null !== $archivePath && file_exists($archivePath)) {
                $fileSize = filesize($archivePath);
                if (false !== $fileSize) {
                    $totalSize += $fileSize;
                }
            }
        }

        return $totalSize;
    }

    /**
     * 获取即将过期的档案
     * @return array<LearnArchive>
     */
    public function getExpiringArchives(int $daysBeforeExpiry = 30): array
    {
        $thresholdDate = (new \DateTimeImmutable())->modify("+{$daysBeforeExpiry} days");

        $result = $this->archiveRepository->createQueryBuilder('la')
            ->andWhere('la.expiryTime <= :threshold')
            ->andWhere('la.archiveStatus = :active')
            ->setParameter('threshold', $thresholdDate)
            ->setParameter('active', ArchiveStatus::ACTIVE)
            ->orderBy('la.expiryTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        // Ensure all items are LearnArchive instances
        return array_filter($result, static fn ($item): bool => $item instanceof LearnArchive);
    }
}
