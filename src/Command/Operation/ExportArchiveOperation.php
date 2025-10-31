<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Exception\RuntimeException;

#[Autoconfigure(public: true)]
class ExportArchiveOperation extends AbstractArchiveOperation
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array
    {
        $io->section('导出档案');

        $archiveId = $config['archiveId'] ?? '';
        $format = $config['format'] ?? '';

        $archiveIdStr = '';
        if (is_string($archiveId)) {
            $archiveIdStr = $archiveId;
        } elseif (is_numeric($archiveId)) {
            $archiveIdStr = (string) $archiveId;
        }

        $formatStr = '';
        if (is_string($format)) {
            $formatStr = $format;
        } elseif (is_numeric($format)) {
            $formatStr = (string) $format;
        }

        $io->text("导出档案: {$archiveIdStr}，格式: {$formatStr}");

        if (true === ($config['dryRun'] ?? false)) {
            return $this->simulateExport($formatStr, $io);
        }

        $exportPath = $config['exportPath'] ?? null;

        return $this->performArchiveExport(
            $archiveIdStr,
            $formatStr,
            $exportPath,
            $io
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): void
    {
        if (null === $config['archiveId']) {
            throw new ArgumentException('导出操作需要指定档案ID');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function simulateExport(string $format, SymfonyStyle $io): array
    {
        $io->success("试运行：档案将导出为 {$format} 格式");

        return [
            'message' => '档案导出完成',
            'exported' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function performArchiveExport(
        string $archiveId,
        string $format,
        mixed $exportPath,
        SymfonyStyle $io,
    ): array {
        try {
            $filePath = $this->archiveService->exportArchive($archiveId, $format);

            if (is_string($exportPath) && $exportPath !== $filePath) {
                $filePath = $this->copyToExportPath($filePath, $exportPath);
            }

            $io->success("档案已导出到: {$filePath}");

            return [
                'message' => '档案导出完成',
                'exported' => 1,
                'path' => $filePath,
            ];
        } catch (\Throwable $e) {
            $this->logError('导出档案', ['archiveId' => $archiveId, 'format' => $format], $e);
            throw $e;
        }
    }

    private function copyToExportPath(string $filePath, string $exportPath): string
    {
        if (!copy($filePath, $exportPath)) {
            throw new RuntimeException("无法复制文件到: {$exportPath}");
        }

        return $exportPath;
    }
}
