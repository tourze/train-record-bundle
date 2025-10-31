<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class VerifyArchiveOperation extends AbstractArchiveOperation
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array
    {
        $io->section('验证档案完整性');

        if (null !== $config['archiveId']) {
            return $this->verifySingleArchive(
                is_string($config['archiveId']) ? $config['archiveId'] : '',
                $io
            );
        }

        return $this->verifyBatchArchives(
            is_string($config['userId']) ? $config['userId'] : null,
            is_string($config['courseId']) ? $config['courseId'] : null,
            is_int($config['batchSize']) ? $config['batchSize'] : 100,
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
    private function verifySingleArchive(string $archiveId, SymfonyStyle $io): array
    {
        $io->text("验证档案: {$archiveId}");

        $verifiedCount = 0;
        $invalidCount = 0;
        $warningCount = 0;

        $result = $this->archiveService->verifyArchiveIntegrity($archiveId);

        if ((bool) $result['isValid']) {
            $verifiedCount = 1;
            $io->success('档案验证通过');
        } else {
            $invalidCount = 1;
            $errors = is_array($result['errors']) ? $result['errors'] : [];
            $io->error('档案验证失败: ' . implode(', ', $errors));
        }

        if ([] !== $result['warnings']) {
            $warningCount = 1;
            $warnings = is_array($result['warnings']) ? $result['warnings'] : [];
            $io->warning('档案警告: ' . implode(', ', $warnings));
        }

        return [
            'message' => sprintf(
                '档案验证完成！通过验证 %d 个，失败 %d 个，警告 %d 个',
                $verifiedCount,
                $invalidCount,
                $warningCount
            ),
            'verified' => $verifiedCount,
            'invalid' => $invalidCount,
            'warnings' => $warningCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyBatchArchives(
        ?string $userId,
        ?string $courseId,
        int $batchSize,
        SymfonyStyle $io,
    ): array {
        $archives = $this->getArchivesToVerify($userId, $courseId);
        $io->text(sprintf('找到 %d 个档案需要验证', count($archives)));

        $progressBar = $io->createProgressBar(count($archives));
        $progressBar->start();

        $results = $this->processBatchVerification($archives, $batchSize, $progressBar);

        $progressBar->finish();
        $io->newLine(2);

        return [
            'message' => sprintf(
                '档案验证完成！验证通过 %d 个，失败 %d 个，警告 %d 个',
                $results['verified'] ?? 0,
                $results['invalid'] ?? 0,
                $results['warnings'] ?? 0
            ),
            'verified' => $results['verified'] ?? 0,
            'invalid' => $results['invalid'] ?? 0,
            'warnings' => $results['warnings'] ?? 0,
        ];
    }

    /**
     * @param array<object> $archives
     * @param mixed $progressBar
     * @return array<string, int>
     */
    private function processBatchVerification(array $archives, int $batchSize, mixed $progressBar): array
    {
        $verifiedCount = 0;
        $invalidCount = 0;
        $warningCount = 0;

        foreach (array_chunk($archives, max(1, $batchSize)) as $batch) {
            foreach ($batch as $archive) {
                $result = $this->verifyArchiveIntegrity($archive);

                $verifiedCount += $result['verified'];
                $invalidCount += $result['invalid'];
                $warningCount += $result['warnings'];

                if (is_object($progressBar) && method_exists($progressBar, 'advance')) {
                    $progressBar->advance();
                }
            }
        }

        return [
            'verified' => $verifiedCount,
            'invalid' => $invalidCount,
            'warnings' => $warningCount,
        ];
    }

    /**
     * @param mixed $archive
     * @return array<string, int>
     */
    private function verifyArchiveIntegrity(mixed $archive): array
    {
        try {
            if (!is_object($archive) || !method_exists($archive, 'getId')) {
                throw new \RuntimeException('Invalid archive object');
            }

            $archiveId = $archive->getId();
            if (null === $archiveId || !is_string($archiveId)) {
                throw new \RuntimeException('Archive ID is null or not a string');
            }

            $result = $this->archiveService->verifyArchiveIntegrity($archiveId);

            $isValid = (bool) ($result['valid'] ?? false);
            $warnings = $result['warnings'] ?? [];

            return [
                'verified' => $isValid ? 1 : 0,
                'invalid' => $isValid ? 0 : 1,
                'warnings' => [] !== $warnings ? 1 : 0,
            ];
        } catch (\Throwable $e) {
            $archiveId = is_object($archive) && method_exists($archive, 'getId') ? $archive->getId() : null;
            $this->logError('验证档案', ['archiveId' => $archiveId], $e);

            return [
                'verified' => 0,
                'invalid' => 1,
                'warnings' => 0,
            ];
        }
    }

    /**
     * @return array<object>
     */
    private function getArchivesToVerify(?string $userId, ?string $courseId): array
    {
        return match (true) {
            null !== $userId && null !== $courseId => $this->findArchiveByUserAndCourse($userId, $courseId),
            null !== $userId => $this->archiveRepository->findByUser($userId),
            default => $this->archiveRepository->findAll(),
        };
    }

    /**
     * @return array<object>
     */
    private function findArchiveByUserAndCourse(string $userId, string $courseId): array
    {
        $archive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);

        return null !== $archive ? [$archive] : [];
    }
}
