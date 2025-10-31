<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class CleanupArchiveOperation extends AbstractArchiveOperation
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array
    {
        $io->section('清理过期档案');

        $daysBeforeExpiry = 30;
        if (isset($config['daysBeforeExpiry']) && is_int($config['daysBeforeExpiry'])) {
            $daysBeforeExpiry = $config['daysBeforeExpiry'];
        } elseif (isset($config['daysBeforeExpiry']) && is_numeric($config['daysBeforeExpiry'])) {
            $daysBeforeExpiry = (int) $config['daysBeforeExpiry'];
        }
        $dryRun = (bool) ($config['dryRun'] ?? false);

        $archiveData = $this->gatherArchiveData($daysBeforeExpiry, $io);
        $cleanedCount = $this->processExpiredArchives($archiveData['expired'], $dryRun, $io);
        $this->displayExpiringWarnings($archiveData['expiring'], $daysBeforeExpiry, $io);

        return [
            'message' => sprintf('档案清理完成！清理了 %d 个过期档案', $cleanedCount),
            'cleaned' => $cleanedCount,
            'expiring' => count($archiveData['expiring']),
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): void
    {
        // 无特殊验证需求
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function gatherArchiveData(int $daysBeforeExpiry, SymfonyStyle $io): array
    {
        $expiredArchives = $this->archiveRepository->findExpired();
        $expiringArchives = $this->archiveService->getExpiringArchives($daysBeforeExpiry);

        $io->text(sprintf(
            '找到 %d 个已过期档案，%d 个即将过期档案',
            count($expiredArchives),
            count($expiringArchives)
        ));

        return [
            'expired' => $expiredArchives,
            'expiring' => $expiringArchives,
        ];
    }

    /**
     * @param array<mixed> $expiredArchives
     */
    private function processExpiredArchives(array $expiredArchives, bool $dryRun, SymfonyStyle $io): int
    {
        if ([] === $expiredArchives) {
            return 0;
        }

        $io->text('清理已过期档案...');

        if ($dryRun) {
            $io->note(sprintf('试运行模式：将清理 %d 个过期档案', count($expiredArchives)));

            return count($expiredArchives);
        }

        try {
            $cleanedCount = $this->archiveService->cleanupExpiredArchives();
            $io->success(sprintf('成功清理 %d 个过期档案', $cleanedCount));

            return $cleanedCount;
        } catch (\Throwable $e) {
            $this->logError('清理过期档案', ['count' => count($expiredArchives)], $e);
            throw $e;
        }
    }

    /**
     * @param array<mixed> $expiringArchives
     */
    private function displayExpiringWarnings(array $expiringArchives, int $daysBeforeExpiry, SymfonyStyle $io): void
    {
        if ([] === $expiringArchives) {
            return;
        }

        $io->warning(sprintf(
            '有 %d 个档案将在 %d 天内过期，请及时处理',
            count($expiringArchives),
            $daysBeforeExpiry
        ));

        $this->displayExpiringArchiveDetails($expiringArchives, $io);
    }

    /**
     * @param array<mixed> $expiringArchives
     */
    private function displayExpiringArchiveDetails(array $expiringArchives, SymfonyStyle $io): void
    {
        foreach ($expiringArchives as $archive) {
            $details = $this->extractArchiveDetails($archive);
            if (null === $details) {
                continue;
            }

            $io->text(sprintf(
                '档案 %s (用户: %s, 课程: %s) 将在 %d 天后过期',
                $details['archiveId'],
                $details['userId'],
                $details['courseId'],
                $details['daysLeft']
            ));
        }
    }

    /**
     * @return array{archiveId: string, userId: string, courseId: string, daysLeft: int}|null
     */
    private function extractArchiveDetails(mixed $archive): ?array
    {
        if (!$this->isValidArchive($archive)) {
            return null;
        }

        assert(is_object($archive) && method_exists($archive, 'getExpiryDate'));

        $expiryDate = $archive->getExpiryDate();
        if (!$expiryDate instanceof \DateTimeInterface) {
            return null;
        }

        $daysLeft = (new \DateTimeImmutable())->diff($expiryDate)->days;
        $courseId = $this->extractCourseId($archive);

        assert(method_exists($archive, 'getId') && method_exists($archive, 'getUserId'));

        return [
            'archiveId' => $this->formatId($archive->getId()),
            'userId' => $this->formatId($archive->getUserId()),
            'courseId' => $courseId,
            'daysLeft' => (int) $daysLeft,
        ];
    }

    private function isValidArchive(mixed $archive): bool
    {
        return is_object($archive)
            && method_exists($archive, 'getExpiryDate')
            && method_exists($archive, 'getId')
            && method_exists($archive, 'getUserId');
    }

    private function extractCourseId(object $archive): string
    {
        if (!method_exists($archive, 'getCourse')) {
            return '未知';
        }

        $course = $archive->getCourse();
        if (!is_object($course) || !method_exists($course, 'getId')) {
            return '未知';
        }

        return $this->formatId($course->getId());
    }

    private function formatId(mixed $id): string
    {
        return is_scalar($id) ? (string) $id : '未知';
    }
}
