<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Monitor;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Service\LearnAnomalyService;

/**
 * 监控告警处理器
 * 负责检测、处理和解决系统告警
 */
class MonitorAlertHandler
{
    public function __construct(
        private readonly LearnAnomalyRepository $anomalyRepository,
        private readonly LearnAnomalyService $anomalyService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 检查告警并自动解决
     * @param array<string, mixed> $data
     */
    public function checkAlertsAndResolve(
        array $data,
        int $alertThreshold,
        bool $autoResolve,
        SymfonyStyle $io,
        bool $quiet,
    ): void {
        $alerts = $this->detectAlerts($data, $alertThreshold);

        if ([] === $alerts) {
            return;
        }

        $this->handleAlerts($alerts, $data, $autoResolve, $io, $quiet);
    }

    /**
     * 检测系统告警
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function detectAlerts(array $data, int $alertThreshold): array
    {
        $anomalyAlerts = $this->checkAnomalyAlerts($data, $alertThreshold);
        $behaviorAlerts = $this->checkBehaviorAlerts($data);
        $systemHealthAlerts = $this->checkSystemHealthAlerts($data);

        return array_merge($anomalyAlerts, $behaviorAlerts, $systemHealthAlerts);
    }

    /**
     * 检查异常告警
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function checkAnomalyAlerts(array $data, int $alertThreshold): array
    {
        $anomaliesData = is_array($data['anomalies'] ?? null) ? $data['anomalies'] : [];
        $anomaliesRecent = is_int($anomaliesData['recent'] ?? null) ? $anomaliesData['recent'] : 0;

        if ($anomaliesRecent >= $alertThreshold) {
            return [sprintf('异常数量过多: %d (阈值: %d)', $anomaliesRecent, $alertThreshold)];
        }

        return [];
    }

    /**
     * 检查行为告警
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function checkBehaviorAlerts(array $data): array
    {
        $behaviorsData = is_array($data['behaviors'] ?? null) ? $data['behaviors'] : [];
        $suspiciousRate = $behaviorsData['suspiciousRate'] ?? 0;
        $suspiciousRateNum = is_numeric($suspiciousRate) ? (float) $suspiciousRate : 0.0;

        if ($suspiciousRateNum > 30) {
            return [sprintf('可疑行为率过高: %.2f%%', $suspiciousRateNum)];
        }

        return [];
    }

    /**
     * 检查系统健康告警
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function checkSystemHealthAlerts(array $data): array
    {
        $systemData = is_array($data['system'] ?? null) ? $data['system'] : [];
        $systemStatus = is_string($systemData['status'] ?? null) ? $systemData['status'] : 'unknown';

        return match ($systemStatus) {
            'critical' => ['系统状态严重异常'],
            'warning' => ['系统状态警告'],
            default => [],
        };
    }

    /**
     * 处理告警
     * @param array<string> $alerts
     * @param array<string, mixed> $data
     */
    private function handleAlerts(array $alerts, array $data, bool $autoResolve, SymfonyStyle $io, bool $quiet): void
    {
        $this->displayAlerts($alerts, $io, $quiet);
        $this->logAlerts($alerts, $data);

        if ($autoResolve) {
            $this->performAutoResolve($data, $io, $quiet);
        }
    }

    /**
     * 显示告警信息
     * @param array<string> $alerts
     */
    private function displayAlerts(array $alerts, SymfonyStyle $io, bool $quiet): void
    {
        if ($quiet) {
            return;
        }

        $io->warning('检测到异常:');
        foreach ($alerts as $alert) {
            $io->text('- ' . $alert);
        }
    }

    /**
     * 记录告警日志
     * @param array<string> $alerts
     * @param array<string, mixed> $data
     */
    private function logAlerts(array $alerts, array $data): void
    {
        $this->logger->warning('系统监控告警', [
            'alerts' => $alerts,
            'data' => $data,
        ]);
    }

    /**
     * 执行自动解决
     * @param array<string, mixed> $data
     */
    private function performAutoResolve(array $data, SymfonyStyle $io, bool $quiet): void
    {
        $resolvedCount = $this->resolveMinorAnomalies();
        $this->reportAutoResolveResults($resolvedCount, $io, $quiet);
    }

    private function resolveMinorAnomalies(): int
    {
        $resolvedCount = 0;
        $unresolvedAnomalies = $this->anomalyRepository->findUnresolved();

        foreach ($unresolvedAnomalies as $anomaly) {
            if ($this->shouldAutoResolve($anomaly)) {
                $resolvedCount += $this->resolveAnomaly($anomaly);
            }
        }

        return $resolvedCount;
    }

    private function shouldAutoResolve(LearnAnomaly $anomaly): bool
    {
        return 'low' === $anomaly->getSeverity()->value;
    }

    private function resolveAnomaly(LearnAnomaly $anomaly): int
    {
        try {
            $anomalyId = $anomaly->getId();
            if (null === $anomalyId) {
                return 0;
            }

            $this->anomalyService->resolveAnomaly(
                $anomalyId,
                '自动解决：系统监控检测到轻微异常',
                'system_monitor'
            );

            return 1;
        } catch (\Throwable $e) {
            $this->logger->error('自动解决异常失败', [
                'anomalyId' => $anomaly->getId(),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    private function reportAutoResolveResults(int $resolvedCount, SymfonyStyle $io, bool $quiet): void
    {
        if ($resolvedCount > 0 && !$quiet) {
            $io->note("自动解决了 {$resolvedCount} 个轻微异常");
        }
    }
}
