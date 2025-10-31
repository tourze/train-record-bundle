<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveDataCollector;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveExporter;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveFileGenerator;
use Tourze\TrainRecordBundle\Service\Archive\ArchiveStatisticsCalculator;

/**
 * 学习档案服务
 *
 * 负责学习记录的归档管理，满足3年保存期限要求
 */
#[WithMonologChannel(channel: 'train_record')]
class LearnArchiveService
{
    // 归档配置常量
    private const ARCHIVE_RETENTION_YEARS = 3;     // 归档保存年限
    private const ARCHIVE_FORMAT_JSON = 'json';    // JSON格式

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LearnArchiveRepository $archiveRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LoggerInterface $logger,
        private readonly ArchiveDataCollector $dataCollector,
        private readonly ArchiveFileGenerator $fileGenerator,
        private readonly ArchiveExporter $exporter,
        private readonly ArchiveStatisticsCalculator $statisticsCalculator,
    ) {
    }

    /**
     * 创建学习档案
     */
    public function createArchive(
        string $userId,
        string $courseId,
        string $format = self::ARCHIVE_FORMAT_JSON,
    ): LearnArchive {
        // 检查是否已存在档案
        $existingArchive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);
        if (null !== $existingArchive) {
            throw new ArgumentException('该用户的课程档案已存在');
        }

        // 收集学习数据
        $archiveData = $this->dataCollector->collectLearningData($userId, $courseId);

        // 生成档案文件
        $archivePath = $this->fileGenerator->generateArchiveFile($userId, $courseId, $archiveData, $format);
        $archiveHash = $this->fileGenerator->calculateFileHash($archivePath);

        // 创建档案记录
        $archive = new LearnArchive();
        $archive->setUserId($userId);
        // TODO: setCourseId is deprecated. This service needs to be refactored to use proper Course entities
        // $archive->setCourseId($courseId);

        /** @var array<string, mixed>|null $sessionSummary */
        $sessionSummary = isset($archiveData['sessionSummary']) && is_array($archiveData['sessionSummary'])
            ? $archiveData['sessionSummary']
            : null;

        /** @var array<string, mixed>|null $behaviorSummary */
        $behaviorSummary = isset($archiveData['behaviorSummary']) && is_array($archiveData['behaviorSummary'])
            ? $archiveData['behaviorSummary']
            : null;

        /** @var array<string, mixed>|null $anomalySummary */
        $anomalySummary = isset($archiveData['anomalySummary']) && is_array($archiveData['anomalySummary'])
            ? $archiveData['anomalySummary']
            : null;

        $archive->setSessionSummary($sessionSummary);
        $archive->setBehaviorSummary($behaviorSummary);
        $archive->setAnomalySummary($anomalySummary);

        $totalEffectiveTime = $archiveData['totalEffectiveTime'] ?? 0;
        $totalSessions = $archiveData['totalSessions'] ?? 0;

        $archive->setTotalEffectiveTime(is_numeric($totalEffectiveTime) ? (float) $totalEffectiveTime : 0.0);
        $archive->setTotalSessions(is_numeric($totalSessions) ? (int) $totalSessions : 0);
        $archive->setArchiveStatus(ArchiveStatus::ACTIVE);
        $archive->setArchiveFormat(ArchiveFormat::from($format));
        $archive->setArchiveTime(new \DateTimeImmutable());
        $archive->setExpiryTime((new \DateTimeImmutable())->modify('+' . self::ARCHIVE_RETENTION_YEARS . ' years'));
        $archive->setArchivePath($archivePath);
        $archive->setArchiveHash($archiveHash);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();

        $this->logger->info('学习档案已创建', [
            'userId' => $userId,
            'courseId' => $courseId,
            'archiveId' => $archive->getId(),
            'format' => $format,
            'path' => $archivePath,
        ]);

        return $archive;
    }

    /**
     * 更新档案
     */
    public function updateArchive(string $archiveId): bool
    {
        $archive = $this->findArchiveOrReturnFalse($archiveId);
        if (null === $archive) {
            return false;
        }

        $userId = $archive->getUserId();
        $courseId = $this->extractCourseId($archive);

        $archiveData = $this->dataCollector->collectLearningData($userId, $courseId);
        $this->regenerateArchiveFile($archive, $userId, $courseId, $archiveData);
        $this->updateArchiveRecord($archive, $archiveData);
        $this->persistChanges($archive);
        $this->logUpdateSuccess($archiveId, $userId, $courseId);

        return true;
    }

    /**
     * 查找档案或返回false
     */
    private function findArchiveOrReturnFalse(string $archiveId): ?LearnArchive
    {
        return $this->archiveRepository->find($archiveId);
    }

    /**
     * 提取课程ID
     */
    private function extractCourseId(LearnArchive $archive): string
    {
        $courseId = $archive->getCourse()->getId();
        if (null === $courseId) {
            throw new \RuntimeException('Course ID cannot be null');
        }

        return $courseId;
    }

    /**
     * 重新生成档案文件
     *
     * @param array<string, mixed> $archiveData
     */
    private function regenerateArchiveFile(LearnArchive $archive, string $userId, string $courseId, array $archiveData): void
    {
        $newArchivePath = $this->fileGenerator->generateArchiveFile(
            $userId,
            $courseId,
            $archiveData,
            $archive->getArchiveFormat()->value
        );
        $newArchiveHash = $this->fileGenerator->calculateFileHash($newArchivePath);

        $this->removeOldArchiveFile($archive);
        $archive->setArchivePath($newArchivePath);
        $archive->setArchiveHash($newArchiveHash);
    }

    /**
     * 删除旧的档案文件
     */
    private function removeOldArchiveFile(LearnArchive $archive): void
    {
        $oldArchivePath = $archive->getArchivePath();
        if (null !== $oldArchivePath && file_exists($oldArchivePath)) {
            unlink($oldArchivePath);
        }
    }

    /**
     * 更新档案记录
     *
     * @param array<string, mixed> $archiveData
     */
    private function updateArchiveRecord(LearnArchive $archive, array $archiveData): void
    {
        /** @var array<string, mixed>|null $sessionSummary */
        $sessionSummary = isset($archiveData['sessionSummary']) && is_array($archiveData['sessionSummary'])
            ? $archiveData['sessionSummary']
            : null;

        /** @var array<string, mixed>|null $behaviorSummary */
        $behaviorSummary = isset($archiveData['behaviorSummary']) && is_array($archiveData['behaviorSummary'])
            ? $archiveData['behaviorSummary']
            : null;

        /** @var array<string, mixed>|null $anomalySummary */
        $anomalySummary = isset($archiveData['anomalySummary']) && is_array($archiveData['anomalySummary'])
            ? $archiveData['anomalySummary']
            : null;

        $archive->setSessionSummary($sessionSummary);
        $archive->setBehaviorSummary($behaviorSummary);
        $archive->setAnomalySummary($anomalySummary);

        $totalEffectiveTime = $archiveData['totalEffectiveTime'] ?? 0;
        $totalSessions = $archiveData['totalSessions'] ?? 0;

        $archive->setTotalEffectiveTime(is_numeric($totalEffectiveTime) ? (float) $totalEffectiveTime : 0.0);
        $archive->setTotalSessions(is_numeric($totalSessions) ? (int) $totalSessions : 0);
    }

    /**
     * 持久化变更
     */
    private function persistChanges(LearnArchive $archive): void
    {
        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    /**
     * 记录更新成功日志
     */
    private function logUpdateSuccess(string $archiveId, string $userId, string $courseId): void
    {
        $this->logger->info('学习档案已更新', [
            'archiveId' => $archiveId,
            'userId' => $userId,
            'courseId' => $courseId,
        ]);
    }

    /**
     * 验证档案完整性
     *
     * @return array<string, mixed>
     */
    public function verifyArchiveIntegrity(string $archiveId): array
    {
        $archive = $this->archiveRepository->find($archiveId);
        if (null === $archive) {
            return ['valid' => false, 'error' => '档案不存在'];
        }

        $archivePath = $archive->getArchivePath();
        if (null === $archivePath || !file_exists($archivePath)) {
            return ['valid' => false, 'error' => '档案文件不存在'];
        }

        $currentHash = $this->fileGenerator->calculateFileHash($archivePath);
        $storedHash = $archive->getArchiveHash();

        if ($currentHash !== $storedHash) {
            return [
                'valid' => false,
                'error' => '档案文件已被篡改',
                'currentHash' => $currentHash,
                'storedHash' => $storedHash,
            ];
        }

        return [
            'valid' => true,
            'fileSize' => false !== filesize($archivePath) ? filesize($archivePath) : 0,
            'lastModified' => false !== filemtime($archivePath) ? filemtime($archivePath) : 0,
            'hash' => $currentHash,
        ];
    }

    /**
     * 获取档案内容
     *
     * @return array<string, mixed>|null
     */
    public function getArchiveContent(string $archiveId): ?array
    {
        $archive = $this->archiveRepository->find($archiveId);
        if (null === $archive) {
            return null;
        }

        $archivePath = $archive->getArchivePath();
        if (null === $archivePath || !file_exists($archivePath)) {
            return null;
        }

        $content = file_get_contents($archivePath);
        if (false === $content) {
            return null;
        }

        return match ($archive->getArchiveFormat()) {
            ArchiveFormat::JSON => $this->parseJsonContent($content),
            ArchiveFormat::XML => $this->fileGenerator->parseXmlContent($content),
            ArchiveFormat::CSV => $this->convertCsvToStringKeyed($this->fileGenerator->parseCsvContent($content)),
            default => ['raw_content' => $content],
        };
    }

    /**
     * 解析JSON内容
     *
     * @return array<string, mixed>|null
     */
    private function parseJsonContent(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> */
        return $decoded;
    }

    /**
     * 将CSV数组转换为字符串键数组
     *
     * @param array<int, array<string, string>> $csvData
     * @return array<string, mixed>
     */
    private function convertCsvToStringKeyed(array $csvData): array
    {
        return ['rows' => $csvData, 'count' => count($csvData)];
    }

    /**
     * 批量归档过期记录
     */
    public function batchArchiveExpiredRecords(\DateTimeImmutable $cutoffDate): int
    {
        $archivedCount = $this->processBatchArchiving($cutoffDate);
        $this->logBatchArchiveCompletion($archivedCount, $cutoffDate);

        return $archivedCount;
    }

    /**
     * 清理过期档案
     */
    public function cleanupExpiredArchives(): int
    {
        $expiredArchives = $this->archiveRepository->findExpiredArchives();
        $cleanedCount = $this->processArchiveCleanup($expiredArchives);

        $this->entityManager->flush();
        $this->logCleanupCompletion($cleanedCount);

        return $cleanedCount;
    }

    /**
     * 获取档案统计
     *
     * @return array<string, mixed>
     */
    public function getArchiveStatistics(): array
    {
        return $this->statisticsCalculator->getArchiveStatistics();
    }

    /**
     * 导出档案为指定格式
     */
    public function exportArchive(string $archiveId, string $format): string
    {
        return $this->exporter->exportArchive($archiveId, $format);
    }

    /**
     * 获取即将过期的档案
     *
     * @return array<LearnArchive>
     */
    public function getExpiringArchives(int $daysBeforeExpiry = 30): array
    {
        return $this->statisticsCalculator->getExpiringArchives($daysBeforeExpiry);
    }

    /**
     * 处理批量归档
     */
    private function processBatchArchiving(\DateTimeImmutable $cutoffDate): int
    {
        $archivedCount = 0;

        while (true) {
            $sessions = $this->sessionRepository->findExpiredSessions($cutoffDate);

            if ([] === $sessions) {
                break;
            }

            $archivedCount += $this->archiveSessionBatch($sessions);
        }

        return $archivedCount;
    }

    /**
     * 归档会话批次
     *
     * @param array<LearnSession> $sessions
     */
    private function archiveSessionBatch(array $sessions): int
    {
        $archivedCount = 0;

        foreach ($sessions as $session) {
            if ($this->archiveSingleSession($session)) {
                ++$archivedCount;
            }
        }

        return $archivedCount;
    }

    /**
     * 归档单个会话
     */
    private function archiveSingleSession(LearnSession $session): bool
    {
        try {
            $userId = $session->getStudent()->getUserIdentifier();
            $courseId = $session->getCourse()->getId();

            if (null === $courseId) {
                throw new \RuntimeException('Course ID cannot be null');
            }

            if ($this->shouldCreateArchive($userId, $courseId)) {
                $this->createArchive($userId, $courseId);

                return true;
            }
        } catch (\Throwable $e) {
            $this->logArchiveError($session, $e);
        }

        return false;
    }

    /**
     * 判断是否应该创建档案
     */
    private function shouldCreateArchive(string $userId, string $courseId): bool
    {
        $existingArchive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);

        return null === $existingArchive;
    }

    /**
     * 记录归档错误
     */
    private function logArchiveError(LearnSession $session, \Throwable $e): void
    {
        $this->logger->error('归档记录失败', [
            'sessionId' => $session->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 记录批量归档完成
     */
    private function logBatchArchiveCompletion(int $archivedCount, \DateTimeImmutable $cutoffDate): void
    {
        $this->logger->info('批量归档完成', [
            'archivedCount' => $archivedCount,
            'cutoffDate' => $cutoffDate->format('Y-m-d'),
        ]);
    }

    /**
     * 处理档案清理
     *
     * @param array<LearnArchive> $expiredArchives
     */
    private function processArchiveCleanup(array $expiredArchives): int
    {
        $cleanedCount = 0;

        foreach ($expiredArchives as $archive) {
            if ($this->cleanupSingleArchive($archive)) {
                ++$cleanedCount;
            }
        }

        return $cleanedCount;
    }

    /**
     * 清理单个档案
     */
    private function cleanupSingleArchive(LearnArchive $archive): bool
    {
        try {
            $this->removeArchiveFile($archive);
            $this->markArchiveAsExpired($archive);

            return true;
        } catch (\Throwable $e) {
            $this->logCleanupError($archive, $e);

            return false;
        }
    }

    /**
     * 删除档案文件
     */
    private function removeArchiveFile(LearnArchive $archive): void
    {
        $archivePath = $archive->getArchivePath();
        if (null !== $archivePath && file_exists($archivePath)) {
            unlink($archivePath);
        }
    }

    /**
     * 标记档案为过期
     */
    private function markArchiveAsExpired(LearnArchive $archive): void
    {
        $archive->setArchiveStatus(ArchiveStatus::EXPIRED);
        $this->entityManager->persist($archive);
    }

    /**
     * 记录清理错误
     */
    private function logCleanupError(LearnArchive $archive, \Throwable $e): void
    {
        $this->logger->error('清理过期档案失败', [
            'archiveId' => $archive->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 记录清理完成
     */
    private function logCleanupCompletion(int $cleanedCount): void
    {
        $this->logger->info('过期档案清理完成', [
            'cleanedCount' => $cleanedCount,
        ]);
    }
}
