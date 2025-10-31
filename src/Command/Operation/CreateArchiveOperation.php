<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Exception\ArgumentException;

#[Autoconfigure(public: true)]
class CreateArchiveOperation extends AbstractArchiveOperation
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array
    {
        $io->section('创建学习档案');

        $archiveFormat = $this->extractArchiveFormat($config);
        $userId = $this->extractUserIdFromConfig($config);
        $courseId = $this->extractCourseIdFromConfig($config);
        $dryRun = $this->extractDryRunFromConfig($config);

        if ($this->isTargetedCreation($userId, $courseId)) {
            $this->validateTargetedCreationParams($userId, $courseId);

            return $this->createSingleArchive(
                (string) $userId,
                (string) $courseId,
                $archiveFormat,
                $dryRun,
                $io
            );
        }

        $batchSize = $this->extractBatchSizeFromConfig($config);

        return $this->createBatchArchives(
            $userId,
            $courseId,
            $archiveFormat,
            $batchSize,
            $dryRun,
            $io
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): void
    {
        $formatValue = $config['format'] ?? null;
        if (!isset($config['format']) || !is_int($formatValue) && !is_string($formatValue) || null === ArchiveFormat::tryFrom($formatValue)) {
            throw new ArgumentException('无效的归档格式');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractArchiveFormat(array $config): ArchiveFormat
    {
        $formatValue = $config['format'] ?? null;
        if (!is_int($formatValue) && !is_string($formatValue)) {
            throw new ArgumentException('归档格式必须是整数或字符串');
        }

        return ArchiveFormat::from($formatValue);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractUserIdFromConfig(array $config): ?string
    {
        $userIdValue = $config['userId'] ?? null;
        if (null === $userIdValue) {
            return null;
        }

        if (is_string($userIdValue)) {
            return $userIdValue;
        }

        if (is_numeric($userIdValue)) {
            return (string) $userIdValue;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractCourseIdFromConfig(array $config): ?string
    {
        $courseIdValue = $config['courseId'] ?? null;
        if (null === $courseIdValue) {
            return null;
        }

        if (is_string($courseIdValue)) {
            return $courseIdValue;
        }

        if (is_numeric($courseIdValue)) {
            return (string) $courseIdValue;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractDryRunFromConfig(array $config): bool
    {
        $dryRunValue = $config['dryRun'] ?? false;

        return is_bool($dryRunValue) ? $dryRunValue : false;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractBatchSizeFromConfig(array $config): int
    {
        $batchSizeValue = $config['batchSize'] ?? 100;

        return is_int($batchSizeValue) ? $batchSizeValue : 100;
    }

    /**
     * @param ?string $userId
     * @param ?string $courseId
     */
    private function validateTargetedCreationParams(?string $userId, ?string $courseId): void
    {
        if (null === $userId || null === $courseId) {
            throw new ArgumentException('用户ID和课程ID不能为空');
        }
    }

    private function isTargetedCreation(?string $userId, ?string $courseId): bool
    {
        return null !== $userId && null !== $courseId;
    }

    /**
     * @return array<string, mixed>
     */
    private function createSingleArchive(
        string $userId,
        string $courseId,
        ArchiveFormat $format,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $io->text("为用户 {$userId} 的课程 {$courseId} 创建档案");

        $createdCount = 0;
        $errorCount = 0;

        if (!$dryRun) {
            try {
                $this->archiveService->createArchive($userId, $courseId, $format->value);
                $createdCount = 1;
            } catch (\Throwable $e) {
                $errorCount = 1;
                $io->error('创建档案失败: ' . $e->getMessage());
                $this->logError('创建档案', ['userId' => $userId, 'courseId' => $courseId], $e);
            }
        } else {
            $createdCount = 1;
        }

        return [
            'message' => sprintf('档案创建完成！成功创建 %d 个档案，失败 %d 个', $createdCount, $errorCount),
            'created' => $createdCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createBatchArchives(
        ?string $userId,
        ?string $courseId,
        ArchiveFormat $format,
        int $batchSize,
        bool $dryRun,
        SymfonyStyle $io,
    ): array {
        $userCourses = $this->prepareBatchCreationData($userId, $courseId, $io);

        $progressBar = $io->createProgressBar(count($userCourses));
        $progressBar->start();

        $results = $this->processBatchWithFlush(
            $userCourses,
            $batchSize,
            $dryRun,
            function (array $batch) use ($format, $dryRun, $progressBar) {
                return $this->processBatch($batch, $format, $dryRun, $progressBar);
            }
        );

        $progressBar->finish();
        $io->newLine(2);

        if (($results['errors'] ?? 0) > 0) {
            $io->warning(sprintf('创建过程中发生 %d 个错误', $results['errors']));
        }

        return [
            'message' => sprintf('档案创建完成！成功创建 %d 个档案，失败 %d 个', $results['created'] ?? 0, $results['errors'] ?? 0),
            'created' => $results['created'] ?? 0,
            'errors' => $results['errors'] ?? 0,
        ];
    }

    /**
     * @return array<mixed>
     */
    private function prepareBatchCreationData(?string $userId, ?string $courseId, SymfonyStyle $io): array
    {
        $completedSessions = $this->getCompletedSessions($userId, $courseId);
        $userCourses = $this->groupSessionsByUserAndCourse($completedSessions);

        $io->text(sprintf('找到 %d 个用户-课程组合需要创建档案', count($userCourses)));

        return $userCourses;
    }

    /**
     * @param array<mixed> $batch
     * @param mixed $progressBar
     * @return array<string, int>
     */
    private function processBatch(array $batch, ArchiveFormat $format, bool $dryRun, mixed $progressBar): array
    {
        $createdCount = 0;
        $errorCount = 0;

        foreach ($batch as $userCourse) {
            try {
                $result = $this->processBatchItem($userCourse, $format, $dryRun);
                $createdCount += $result['created'];
                $errorCount += $result['errors'];
            } catch (\Throwable $e) {
                ++$errorCount;
                /** @var array<string, mixed> $context */
                $context = is_array($userCourse) ? $userCourse : ['userCourse' => $userCourse];
                $this->logError('创建档案', $context, $e);
            }

            $this->advanceProgressBar($progressBar);
        }

        return ['created' => $createdCount, 'errors' => $errorCount];
    }

    /**
     * 处理批次项目
     * @param mixed $userCourse
     * @return array<string, int>
     */
    private function processBatchItem(mixed $userCourse, ArchiveFormat $format, bool $dryRun): array
    {
        if (!is_array($userCourse)) {
            return ['created' => 0, 'errors' => 0];
        }

        // 确保数组键的类型正确
        /** @var array<string, mixed> $userCourse */
        $userCourse = array_change_key_case($userCourse, CASE_LOWER);
        if (!$this->shouldProcessArchive($userCourse, $dryRun)) {
            return ['created' => 0, 'errors' => 0];
        }

        $userId = $this->extractUserId($userCourse);
        $courseId = $this->extractCourseId($userCourse);

        $this->archiveService->createArchive($userId, $courseId, $format->value);

        return ['created' => 1, 'errors' => 0];
    }

    /**
     * 提取用户ID
     * @param array<string, mixed> $userCourse
     */
    private function extractUserId(array $userCourse): string
    {
        $userId = $userCourse['userid'] ?? $userCourse['userId'] ?? '';
        if (is_string($userId)) {
            return $userId;
        }
        if (is_numeric($userId)) {
            return (string) $userId;
        }

        return '';
    }

    /**
     * 提取课程ID
     * @param array<string, mixed> $userCourse
     */
    private function extractCourseId(array $userCourse): string
    {
        $courseId = $userCourse['courseid'] ?? $userCourse['courseId'] ?? '';
        if (is_string($courseId)) {
            return $courseId;
        }
        if (is_numeric($courseId)) {
            return (string) $courseId;
        }

        return '';
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
     * @param array<string, mixed> $userCourse
     */
    private function shouldProcessArchive(array $userCourse, bool $dryRun): bool
    {
        if ($dryRun) {
            return true;
        }

        $userId = $this->extractUserId($userCourse);
        $courseId = $this->extractCourseId($userCourse);

        $existingArchive = $this->archiveRepository->findByUserAndCourse(
            $userId,
            $courseId
        );

        return null === $existingArchive;
    }
}
