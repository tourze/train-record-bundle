<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Monitor;

/**
 * 监控日志服务
 * 负责记录监控数据到日志文件
 */
class MonitorLogger
{
    /**
     * 写入日志文件
     * @param array<string, mixed> $data
     * @param resource $handle
     */
    public function writeToLogFile(array $data, $handle): void
    {
        $timestamp = is_scalar($data['timestamp'] ?? '') ? (string) ($data['timestamp'] ?? '') : '';
        $sessionsData = is_array($data['sessions'] ?? null) ? $data['sessions'] : [];
        $anomaliesData = is_array($data['anomalies'] ?? null) ? $data['anomalies'] : [];
        $behaviorsData = is_array($data['behaviors'] ?? null) ? $data['behaviors'] : [];
        $systemData = is_array($data['system'] ?? null) ? $data['system'] : [];

        $sessionsActive = is_int($sessionsData['active'] ?? null) ? $sessionsData['active'] : 0;
        $anomaliesRecent = is_int($anomaliesData['recent'] ?? null) ? $anomaliesData['recent'] : 0;
        $behaviorsSuspicious = is_int($behaviorsData['suspicious'] ?? null) ? $behaviorsData['suspicious'] : 0;
        $systemStatus = is_string($systemData['status'] ?? null) ? $systemData['status'] : 'unknown';
        $systemScore = is_int($systemData['score'] ?? null) ? $systemData['score'] : 0;

        $logLine = sprintf(
            "[%s] Sessions: %d, Anomalies: %d, Suspicious: %d, Health: %s (%d)\n",
            $timestamp,
            $sessionsActive,
            $anomaliesRecent,
            $behaviorsSuspicious,
            $systemStatus,
            $systemScore
        );

        fwrite($handle, $logLine);
        fflush($handle);
    }
}
