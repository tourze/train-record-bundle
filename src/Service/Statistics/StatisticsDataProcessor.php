<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Statistics;

use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;
use Tourze\TrainRecordBundle\Exception\ArgumentException;

/**
 * 统计数据处理器
 * 负责处理统计数据的持久化和导出
 */
class StatisticsDataProcessor
{
    /**
     * 保存统计数据
     * @param array<string, mixed> $data
     */
    public function saveStatistics(
        StatisticsType $type,
        StatisticsPeriod $period,
        array $data,
        string $scopeId,
    ): void {
        // TODO: Implement statistics saving
        // $this->analyticsService->createStatistics($type, $period, $scopeId, $data);
    }

    /**
     * 导出统计数据
     * @param array<string, mixed> $data
     */
    public function exportStatistics(array $data, string $filePath, string $format): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        switch ($format) {
            case 'json':
                $this->exportJsonFormat($data, $filePath);
                break;

            case 'csv':
                $this->exportCsvFormat($data, $filePath);
                break;

            default:
                throw new ArgumentException("不支持的导出格式: {$format}");
        }
    }

    /**
     * 导出JSON格式
     * @param array<string, mixed> $data
     */
    private function exportJsonFormat(array $data, string $filePath): void
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, false !== $jsonOutput ? $jsonOutput : '{}');
    }

    /**
     * 导出CSV格式
     * @param array<string, mixed> $data
     */
    private function exportCsvFormat(array $data, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        if (false === $handle) {
            throw new ArgumentException('Failed to open file for writing: ' . $filePath);
        }
        fputcsv($handle, ['指标', '数值']);

        $this->writeCsvData($data, $handle);

        fclose($handle);
    }

    /**
     * 写入CSV数据
     * @param array<string, mixed> $data
     * @param resource $handle
     */
    private function writeCsvData(array $data, $handle, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;
            $fullKey = ('' !== $prefix) ? $prefix . '.' . $keyStr : $keyStr;

            if (is_array($value)) {
                /** @var array<string, mixed> $valueArray */
                $valueArray = $value;
                $this->writeCsvData($valueArray, $handle, $fullKey);
            } else {
                $valueStr = is_scalar($value) ? (string) $value : '';
                fputcsv($handle, [$fullKey, $valueStr]);
            }
        }
    }

    /**
     * 处理统计持久化
     * @param array<string, mixed> $statisticsData
     */
    public function handleStatisticsPersistence(
        StatisticsType $statisticsType,
        StatisticsPeriod $statisticsPeriod,
        array $statisticsData,
        ?string $userId,
        ?string $courseId,
        bool $save,
        SymfonyStyle $io,
    ): void {
        if ($save) {
            $this->saveStatistics($statisticsType, $statisticsPeriod, $statisticsData, $userId ?? $courseId ?? 'global');
            $io->note('统计数据已保存到数据库');
        }
    }

    /**
     * 处理统计导出
     * @param array<string, mixed> $statisticsData
     */
    public function handleStatisticsExport(
        array $statisticsData,
        ?string $exportPath,
        string $format,
        SymfonyStyle $io,
    ): void {
        if (null !== $exportPath) {
            $this->exportStatistics($statisticsData, $exportPath, $format);
            $io->note("统计数据已导出到: {$exportPath}");
        }
    }
}
