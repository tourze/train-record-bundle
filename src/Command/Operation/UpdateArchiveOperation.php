<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

#[Autoconfigure(public: true)]
class UpdateArchiveOperation extends AbstractArchiveOperation
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array
    {
        $io->section('更新学习档案');

        if (null !== $config['archiveId']) {
            return $this->updateSingleArchive(
                is_string($config['archiveId']) ? $config['archiveId'] : '',
                is_bool($config['dryRun']) ? $config['dryRun'] : false,
                $io
            );
        }

        return $this->updateBatchArchives(
            is_string($config['userId']) ? $config['userId'] : null,
            is_string($config['courseId']) ? $config['courseId'] : null,
            is_int($config['batchSize']) ? $config['batchSize'] : 100,
            is_bool($config['dryRun']) ? $config['dryRun'] : false,
            $io
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): void
    {
        // 无特殊验证需求
    }

    /**
     * @return array<string, mixed>
     */
    private function updateSingleArchive(string $archiveId, bool $dryRun, SymfonyStyle $io): array
    {
        $io->text("更新档案: {$archiveId}");

        $updatedCount = 0;
        $errorCount = 0;

        if (!$dryRun) {
            try {
                $this->archiveService->updateArchive($archiveId);
                $updatedCount = 1;
            } catch (\Throwable $e) {
                $errorCount = 1;
                $io->error('更新档案失败: ' . $e->getMessage());
                $this->logError('更新档案', ['archiveId' => $archiveId], $e);
            }
        } else {
            $updatedCount = 1;
        }

        return [
            'message' => sprintf('档案更新完成！成功更新 %d 个档案，失败 %d 个', $updatedCount, $errorCount),
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updateBatchArchives(
        ?string $userId,
        ?string $courseId,
        int $batchSize,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $archives = $this->getArchivesToUpdate($userId, $courseId);
        $io->text(sprintf('找到 %d 个档案需要更新', count($archives)));

        $progressBar = $io->createProgressBar(count($archives));
        $progressBar->start();

        $results = $this->processBatchWithFlush(
            $archives,
            $batchSize,
            $dryRun,
            function (array $batch) use ($dryRun, $progressBar) {
                return $this->processUpdateBatch($batch, $dryRun, $progressBar);
            }
        );

        $progressBar->finish();
        $io->newLine(2);

        if (($results['errors'] ?? 0) > 0) {
            $io->warning(sprintf('更新过程中发生 %d 个错误', $results['errors']));
        }

        return [
            'message' => sprintf('档案更新完成！成功更新 %d 个档案，失败 %d 个', $results['updated'] ?? 0, $results['errors'] ?? 0),
            'updated' => $results['updated'] ?? 0,
            'errors' => $results['errors'] ?? 0,
        ];
    }

    /**
     * @param array<mixed> $batch
     * @param mixed $progressBar
     * @return array<string, int>
     */
    private function processUpdateBatch(array $batch, bool $dryRun, mixed $progressBar): array
    {
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($batch as $archive) {
            try {
                $this->processArchiveItem($archive, $dryRun);
                ++$updatedCount;
            } catch (\Throwable $e) {
                ++$errorCount;
                $this->handleArchiveError($archive, $e);
            }

            $this->advanceProgressBar($progressBar);
        }

        return ['updated' => $updatedCount, 'errors' => $errorCount];
    }

    /**
     * 处理单个档案项
     */
    private function processArchiveItem(mixed $archive, bool $dryRun): void
    {
        if (!$this->shouldUpdateArchive($archive, $dryRun)) {
            return;
        }

        $archiveId = $this->getArchiveId($archive);
        $this->archiveService->updateArchive($archiveId);
    }

    /**
     * 判断是否应该更新档案
     */
    private function shouldUpdateArchive(mixed $archive, bool $dryRun): bool
    {
        return !$dryRun && $this->isValidArchive($archive);
    }

    /**
     * 检查档案是否有效
     */
    private function isValidArchive(mixed $archive): bool
    {
        return is_object($archive) && method_exists($archive, 'getId');
    }

    /**
     * 获取档案ID
     */
    private function getArchiveId(mixed $archive): string
    {
        $archiveId = is_object($archive) && method_exists($archive, 'getId')
            ? $archive->getId()
            : null;

        if (null === $archiveId || !is_string($archiveId)) {
            throw new \RuntimeException('无效的档案ID');
        }

        return $archiveId;
    }

    /**
     * 处理档案错误
     */
    private function handleArchiveError(mixed $archive, \Throwable $e): void
    {
        $archiveId = is_object($archive) && method_exists($archive, 'getId')
            ? $archive->getId()
            : null;
        $this->logError('更新档案', ['archiveId' => $archiveId], $e);
    }

    /**
     * 推进进度条
     */
    private function advanceProgressBar(mixed $progressBar): void
    {
        if (is_object($progressBar) && method_exists($progressBar, 'advance')) {
            $progressBar->advance();
        }
    }

    /**
     * @return array<mixed>
     */
    private function getArchivesToUpdate(?string $userId, ?string $courseId): array
    {
        return match (true) {
            null !== $userId && null !== $courseId => $this->findArchiveByUserAndCourse($userId, $courseId),
            null !== $userId => $this->archiveRepository->findByUser($userId),
            default => $this->archiveRepository->findByStatus(ArchiveStatus::ACTIVE),
        };
    }

    /**
     * @return array<mixed>
     */
    private function findArchiveByUserAndCourse(string $userId, string $courseId): array
    {
        $archive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);

        return null !== $archive ? [$archive] : [];
    }
}
