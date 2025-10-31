<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Monitor;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 监控数据展示器
 * 负责以不同格式展示监控数据
 */
class MonitorDataDisplayer
{
    /**
     * 显示监控数据
     * @param array<string, mixed> $data
     */
    public function displayMonitoringData(array $data, string $format, SymfonyStyle $io): void
    {
        match ($format) {
            'json' => $this->displayJsonFormat($data, $io),
            'simple' => $this->displaySimpleFormat($data, $io),
            default => $this->displayTableFormat($data, $io),
        };
    }

    /**
     * 显示JSON格式
     * @param array<string, mixed> $data
     */
    private function displayJsonFormat(array $data, SymfonyStyle $io): void
    {
        $jsonOutput = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $io->text(false !== $jsonOutput ? $jsonOutput : 'JSON encoding failed');
    }

    /**
     * 显示简单格式
     * @param array<string, mixed> $data
     */
    private function displaySimpleFormat(array $data, SymfonyStyle $io): void
    {
        $simpleData = $this->extractSimpleFormatData($data);

        $io->text(sprintf(
            '[%s] 活跃会话: %d, 异常: %d, 可疑行为: %d, 系统状态: %s',
            $simpleData['timestamp'],
            $simpleData['sessionsActive'],
            $simpleData['anomaliesRecent'],
            $simpleData['behaviorsSuspicious'],
            $simpleData['systemStatus']
        ));
    }

    /**
     * 提取简单格式数据
     * @param array<string, mixed> $data
     * @return array{timestamp: string, sessionsActive: int, anomaliesRecent: int, behaviorsSuspicious: int, systemStatus: string}
     */
    private function extractSimpleFormatData(array $data): array
    {
        $sessions = is_array($data['sessions'] ?? null) ? $data['sessions'] : [];
        $anomalies = is_array($data['anomalies'] ?? null) ? $data['anomalies'] : [];
        $behaviors = is_array($data['behaviors'] ?? null) ? $data['behaviors'] : [];
        $system = is_array($data['system'] ?? null) ? $data['system'] : [];

        /** @var array<string, mixed> $sessions */
        /** @var array<string, mixed> $anomalies */
        /** @var array<string, mixed> $behaviors */
        /** @var array<string, mixed> $system */

        return [
            'timestamp' => is_scalar($data['timestamp'] ?? '') ? (string) ($data['timestamp'] ?? '') : '',
            'sessionsActive' => $this->extractCountFromData($sessions, 'activeCount'),
            'anomaliesRecent' => $this->extractCountFromData($anomalies, 'recentCount'),
            'behaviorsSuspicious' => $this->extractCountFromData($behaviors, 'suspiciousCount'),
            'systemStatus' => isset($system['status']) && is_scalar($system['status']) ? (string) $system['status'] : 'unknown',
        ];
    }

    /**
     * 从数据数组中提取计数值
     * @param array<string, mixed> $data
     */
    private function extractCountFromData(array $data, string $key): int
    {
        return is_int($data[$key] ?? null) ? $data[$key] : 0;
    }

    /**
     * 表格格式显示
     * @param array<string, mixed> $data
     */
    private function displayTableFormat(array $data, SymfonyStyle $io): void
    {
        $timestamp = is_scalar($data['timestamp'] ?? '') ? (string) ($data['timestamp'] ?? '') : '';
        $io->section('系统监控 - ' . $timestamp);

        $this->displayMonitoringTables($data, $io);
    }

    /**
     * 显示所有监控表格
     * @param array<string, mixed> $data
     */
    private function displayMonitoringTables(array $data, SymfonyStyle $io): void
    {
        $sessions = is_array($data['sessions'] ?? null) ? $data['sessions'] : [];
        $anomalies = is_array($data['anomalies'] ?? null) ? $data['anomalies'] : [];
        $behaviors = is_array($data['behaviors'] ?? null) ? $data['behaviors'] : [];
        $devices = is_array($data['devices'] ?? null) ? $data['devices'] : [];
        $system = is_array($data['system'] ?? null) ? $data['system'] : [];

        /** @var array<string, mixed> $sessions */
        /** @var array<string, mixed> $anomalies */
        /** @var array<string, mixed> $behaviors */
        /** @var array<string, mixed> $devices */
        /** @var array<string, mixed> $system */
        $this->displaySessionsTable($sessions, $io);
        $this->displayAnomaliesTable($anomalies, $io);
        $this->displayBehaviorsTable($behaviors, $io);
        $this->displayDevicesTable($devices, $io);
        $this->displaySystemHealth($system, $io);
    }

    /**
     * 显示会话统计表格
     * @param array<string, mixed> $sessionsData
     */
    private function displaySessionsTable(array $sessionsData, SymfonyStyle $io): void
    {
        $io->table(['会话指标', '数值'], [
            ['活跃会话', $this->extractCountFromData($sessionsData, 'activeCount')],
            ['最近1小时会话', $this->extractCountFromData($sessionsData, 'recentCount')],
        ]);
    }

    /**
     * 显示异常统计表格
     * @param array<string, mixed> $anomaliesData
     */
    private function displayAnomaliesTable(array $anomaliesData, SymfonyStyle $io): void
    {
        $io->table(['异常指标', '数值'], [
            ['最近1小时异常', $this->extractCountFromData($anomaliesData, 'recentCount')],
            ['未解决异常', $this->extractCountFromData($anomaliesData, 'unresolvedCount')],
        ]);
    }

    /**
     * 显示行为统计表格
     * @param array<string, mixed> $behaviorsData
     */
    private function displayBehaviorsTable(array $behaviorsData, SymfonyStyle $io): void
    {
        $suspiciousRate = $this->formatSuspiciousRate($behaviorsData['suspiciousRate'] ?? 0);

        $io->table(['行为指标', '数值'], [
            ['最近1小时行为', $this->extractCountFromData($behaviorsData, 'totalCount')],
            ['可疑行为', $this->extractCountFromData($behaviorsData, 'suspiciousCount')],
            ['可疑率', $suspiciousRate],
        ]);
    }

    /**
     * 显示设备统计表格
     * @param array<string, mixed> $devicesData
     */
    private function displayDevicesTable(array $devicesData, SymfonyStyle $io): void
    {
        $io->table(['设备指标', '数值'], [
            ['活跃设备', $this->extractCountFromData($devicesData, 'activeCount')],
            ['最近活跃设备', $this->extractCountFromData($devicesData, 'recentCount')],
        ]);
    }

    /**
     * 显示系统健康状态
     * @param array<string, mixed> $systemData
     */
    private function displaySystemHealth(array $systemData, SymfonyStyle $io): void
    {
        $systemStatus = is_string($systemData['status'] ?? null) ? $systemData['status'] : 'unknown';
        $healthColor = $this->getHealthColor($systemStatus);
        $systemScore = $this->extractCountFromData($systemData, 'score');

        $io->text(sprintf(
            '系统状态: <%s>%s</> (健康分数: %d/100)',
            $healthColor,
            strtoupper($systemStatus),
            $systemScore
        ));

        $io->newLine();
    }

    /**
     * 格式化可疑率
     */
    private function formatSuspiciousRate(mixed $rate): string
    {
        $rateValue = is_numeric($rate) ? (string) $rate : '0';

        return $rateValue . '%';
    }

    /**
     * 获取健康状态颜色
     */
    private function getHealthColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };
    }
}
