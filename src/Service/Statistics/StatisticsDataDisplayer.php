<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Statistics;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 统计数据展示器
 * 负责以不同格式展示统计数据
 */
class StatisticsDataDisplayer
{
    /**
     * 显示统计结果
     * @param array<string, mixed> $data
     */
    public function displayStatistics(array $data, string $format, SymfonyStyle $io): void
    {
        match ($format) {
            'json' => $this->displayJsonFormat($data, $io),
            'csv' => $this->displayCsvFormat($data, $io),
            default => $this->displayTableFormat($data, $io),
        };
    }

    /**
     * JSON格式显示
     * @param array<string, mixed> $data
     */
    private function displayJsonFormat(array $data, SymfonyStyle $io): void
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $io->text(false !== $jsonOutput ? $jsonOutput : 'JSON encoding failed');
    }

    /**
     * 表格格式显示
     * @param array<string, mixed> $data
     */
    private function displayTableFormat(array $data, SymfonyStyle $io): void
    {
        $this->displayOverviewSection($data, $io);
        $this->displayUserMetricsSection($data, $io);
        $this->displayCourseMetricsSection($data, $io);
        $this->displayBehaviorAnalysisSection($data, $io);
    }

    /**
     * 显示概览部分
     * @param array<string, mixed> $data
     */
    private function displayOverviewSection(array $data, SymfonyStyle $io): void
    {
        if (!isset($data['overview']) || !is_array($data['overview'])) {
            return;
        }

        /** @var array<string, mixed> $overview */
        $overview = $data['overview'];
        $io->section('概览');
        $rows = $this->buildOverviewRows($overview);
        $io->table(['指标', '数值'], $rows);
    }

    /**
     * 构建概览行数据
     * @param array<string, mixed> $overview
     * @return array<array<string>>
     */
    private function buildOverviewRows(array $overview): array
    {
        $rows = [];
        foreach ($overview as $key => $value) {
            $keyStr = (string) $key;
            $rows[] = [ucfirst($keyStr), $this->formatValue($value)];
        }

        return $rows;
    }

    /**
     * 格式化数值
     * @param mixed $value
     */
    private function formatValue(mixed $value): string
    {
        if (is_numeric($value)) {
            return number_format((float) $value, 2);
        }
        if (is_string($value)) {
            return $value;
        }

        return '';
    }

    /**
     * 显示用户指标部分
     * @param array<string, mixed> $data
     */
    private function displayUserMetricsSection(array $data, SymfonyStyle $io): void
    {
        if (!isset($data['userMetrics']) || !is_array($data['userMetrics'])) {
            return;
        }

        /** @var array<string, mixed> $metrics */
        $metrics = $data['userMetrics'];
        $io->section('用户指标');
        $this->displayMetricsTable($metrics, $io);
    }

    /**
     * 显示课程指标部分
     * @param array<string, mixed> $data
     */
    private function displayCourseMetricsSection(array $data, SymfonyStyle $io): void
    {
        if (!isset($data['courseMetrics']) || !is_array($data['courseMetrics'])) {
            return;
        }

        /** @var array<string, mixed> $metrics */
        $metrics = $data['courseMetrics'];
        $io->section('课程指标');
        $this->displayMetricsTable($metrics, $io);
    }

    /**
     * 显示行为分析部分
     * @param array<string, mixed> $data
     */
    private function displayBehaviorAnalysisSection(array $data, SymfonyStyle $io): void
    {
        if (!isset($data['behaviorAnalysis']) || !is_array($data['behaviorAnalysis'])) {
            return;
        }

        /** @var array<string, mixed> $metrics */
        $metrics = $data['behaviorAnalysis'];
        $io->section('行为分析');
        $this->displayMetricsTable($metrics, $io);
    }

    /**
     * 显示指标表格
     * @param array<string, mixed> $metrics
     */
    private function displayMetricsTable(array $metrics, SymfonyStyle $io): void
    {
        $rows = $this->buildMetricsRows($metrics);
        $io->table(['指标', '数值'], $rows);
    }

    /**
     * 构建指标行数据
     * @param array<string, mixed> $metrics
     * @return array<array<string>>
     */
    private function buildMetricsRows(array $metrics): array
    {
        $rows = [];
        foreach ($metrics as $key => $value) {
            $rows[] = $this->buildMetricRow($key, $value);
        }

        return $rows;
    }

    /**
     * 构建单个指标行
     * @param mixed $key
     * @param mixed $value
     * @return array<string>
     */
    private function buildMetricRow(mixed $key, mixed $value): array
    {
        $keyStr = is_scalar($key) ? (string) $key : 'unknown';
        $formattedValue = $this->formatMetricValue($value);

        return [ucfirst($keyStr), $formattedValue];
    }

    /**
     * 格式化指标值
     * @param mixed $value
     */
    private function formatMetricValue(mixed $value): string
    {
        if (is_array($value)) {
            $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);

            return false !== $jsonValue ? $jsonValue : '';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2);
        }

        return is_string($value) ? $value : '';
    }

    /**
     * CSV格式显示
     * @param array<string, mixed> $data
     */
    private function displayCsvFormat(array $data, SymfonyStyle $io): void
    {
        $io->text('指标,数值');
        $this->outputCsvData($data, $io);
    }

    /**
     * 输出CSV数据
     * @param array<string, mixed> $data
     */
    private function outputCsvData(array $data, SymfonyStyle $io, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $this->processCsvDataItem($key, $value, $io, $prefix);
        }
    }

    /**
     * 处理CSV数据项
     * @param mixed $key
     * @param mixed $value
     */
    private function processCsvDataItem(mixed $key, mixed $value, SymfonyStyle $io, string $prefix): void
    {
        $keyStr = is_scalar($key) ? (string) $key : 'unknown';
        $fullKey = $this->buildFullKey($prefix, $keyStr);

        if (is_array($value)) {
            /** @var array<string, mixed> $valueArray */
            $valueArray = $value;
            $this->outputCsvData($valueArray, $io, $fullKey);
        } else {
            $this->outputCsvLine($fullKey, $value, $io);
        }
    }

    /**
     * 构建完整键名
     */
    private function buildFullKey(string $prefix, string $key): string
    {
        return ('' !== $prefix) ? $prefix . '.' . $key : $key;
    }

    /**
     * 输出CSV行
     * @param mixed $value
     */
    private function outputCsvLine(string $key, mixed $value, SymfonyStyle $io): void
    {
        $valueStr = is_scalar($value) ? (string) $value : '';
        $io->text(sprintf('%s,%s', $key, $valueStr));
    }
}
