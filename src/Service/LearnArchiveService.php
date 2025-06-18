<?php

namespace Tourze\TrainRecordBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnAnomalyRepository;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\Repository\LearnBehaviorRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 学习档案服务
 * 
 * 负责学习记录的归档管理，满足3年保存期限要求
 */
class LearnArchiveService
{
    // 归档配置常量
    private const ARCHIVE_RETENTION_YEARS = 3;     // 归档保存年限
    private const ARCHIVE_BATCH_SIZE = 100;        // 批量处理大小
    private const ARCHIVE_FORMAT_JSON = 'json';    // JSON格式
    private const ARCHIVE_FORMAT_XML = 'xml';      // XML格式
    private const ARCHIVE_FORMAT_PDF = 'pdf';      // PDF格式

    public function __construct(
                private readonly LearnArchiveRepository $archiveRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnBehaviorRepository $behaviorRepository,
        private readonly LearnAnomalyRepository $anomalyRepository,
        private readonly LoggerInterface $logger,
        private readonly string $archiveStoragePath = '/var/archives/learn_records',
    ) {
    }

    /**
     * 创建学习档案
     */
    public function createArchive(
        string $userId,
        string $courseId,
        string $format = self::ARCHIVE_FORMAT_JSON
    ): LearnArchive {
        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if ($course === null) {
            throw new \InvalidArgumentException('课程不存在');
        }

        // 检查是否已存在档案
        $existingArchive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);
        if ($existingArchive !== null) {
            throw new \InvalidArgumentException('该用户的课程档案已存在');
        }

        // 收集学习数据
        $archiveData = $this->collectLearningData($userId, $courseId);

        // 生成档案文件
        $archivePath = $this->generateArchiveFile($userId, $courseId, $archiveData, $format);
        $archiveHash = $this->calculateFileHash($archivePath);

        // 创建档案记录
        $archive = new LearnArchive();
        $archive->setUserId($userId);
        $archive->setCourse($course);
        $archive->setSessionSummary($archiveData['sessionSummary']);
        $archive->setBehaviorSummary($archiveData['behaviorSummary']);
        $archive->setAnomalySummary($archiveData['anomalySummary']);
        $archive->setTotalEffectiveTime($archiveData['totalEffectiveTime']);
        $archive->setTotalSessions($archiveData['totalSessions']);
        $archive->setArchiveStatus(ArchiveStatus::ACTIVE);
        $archive->setArchiveFormat(ArchiveFormat::from($format));
        $archive->setArchiveDate(new \DateTimeImmutable());
        $archive->setExpiryDate((new \DateTimeImmutable())->modify('+' . self::ARCHIVE_RETENTION_YEARS . ' years'));
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
        $archive = $this->archiveRepository->find($archiveId);
        if ($archive === null) {
            return false;
        }

        $userId = $archive->getUserId();
        $courseId = $archive->getCourse()->getId();

        // 重新收集数据
        $archiveData = $this->collectLearningData($userId, $courseId);

        // 更新档案文件
        $newArchivePath = $this->generateArchiveFile(
            $userId, 
            $courseId, 
            $archiveData, 
            $archive->getArchiveFormat()->value
        );
        $newArchiveHash = $this->calculateFileHash($newArchivePath);

        // 删除旧文件
        if (file_exists($archive->getArchivePath())) {
            unlink($archive->getArchivePath());
        }

        // 更新档案记录
        $archive->setSessionSummary($archiveData['sessionSummary']);
        $archive->setBehaviorSummary($archiveData['behaviorSummary']);
        $archive->setAnomalySummary($archiveData['anomalySummary']);
        $archive->setTotalEffectiveTime($archiveData['totalEffectiveTime']);
        $archive->setTotalSessions($archiveData['totalSessions']);
        $archive->setArchivePath($newArchivePath);
        $archive->setArchiveHash($newArchiveHash);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();

        $this->logger->info('学习档案已更新', [
            'archiveId' => $archiveId,
            'userId' => $userId,
            'courseId' => $courseId,
        ]);

        return true;
    }

    /**
     * 验证档案完整性
     */
    public function verifyArchiveIntegrity(string $archiveId): array
    {
        $archive = $this->archiveRepository->find($archiveId);
        if ($archive === null) {
            return ['valid' => false, 'error' => '档案不存在'];
        }

        $archivePath = $archive->getArchivePath();
        if (!file_exists($archivePath)) {
            return ['valid' => false, 'error' => '档案文件不存在'];
        }

        $currentHash = $this->calculateFileHash($archivePath);
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
            'fileSize' => filesize($archivePath),
            'lastModified' => filemtime($archivePath),
            'hash' => $currentHash,
        ];
    }

    /**
     * 获取档案内容
     */
    public function getArchiveContent(string $archiveId): ?array
    {
        $archive = $this->archiveRepository->find($archiveId);
        if ($archive === null) {
            return null;
        }

        $archivePath = $archive->getArchivePath();
        if (!file_exists($archivePath)) {
            return null;
        }

        $content = file_get_contents($archivePath);
        
        return match ($archive->getArchiveFormat()) {
            self::ARCHIVE_FORMAT_JSON => json_decode($content, true),
            self::ARCHIVE_FORMAT_XML => $this->parseXmlContent($content),
            default => ['raw_content' => $content],
        };
    }

    /**
     * 批量归档过期记录
     */
    public function batchArchiveExpiredRecords(\DateTimeInterface $cutoffDate): int
    {
        $archivedCount = 0;
        $offset = 0;

        while (true) {
            $sessions = $this->sessionRepository->findExpiredSessions($cutoffDate, self::ARCHIVE_BATCH_SIZE, $offset);
            
            if (empty($sessions)) {
                break;
            }

            foreach ($sessions as $session) {
                try {
                    $userId = $session->getStudent()->getId();
                    $courseId = $session->getCourse()->getId();

                    // 检查是否已有档案
                    $existingArchive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);
                    
                    if ($existingArchive === null) {
                        $this->createArchive($userId, $courseId);
                        $archivedCount++;
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('归档记录失败', [
                        'sessionId' => $session->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $offset += self::ARCHIVE_BATCH_SIZE;
        }

        $this->logger->info('批量归档完成', [
            'archivedCount' => $archivedCount,
            'cutoffDate' => $cutoffDate->format('Y-m-d'),
        ]);

        return $archivedCount;
    }

    /**
     * 清理过期档案
     */
    public function cleanupExpiredArchives(): int
    {
        $expiredArchives = $this->archiveRepository->findExpiredArchives();
        $cleanedCount = 0;

        foreach ($expiredArchives as $archive) {
            try {
                // 删除档案文件
                if (file_exists($archive->getArchivePath())) {
                    unlink($archive->getArchivePath());
                }

                // 更新档案状态
                $archive->setArchiveStatus('expired');
                $this->entityManager->persist($archive);
                
                $cleanedCount++;
            } catch (\Throwable $e) {
                $this->logger->error('清理过期档案失败', [
                    'archiveId' => $archive->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('过期档案清理完成', [
            'cleanedCount' => $cleanedCount,
        ]);

        return $cleanedCount;
    }

    /**
     * 获取档案统计
     */
    public function getArchiveStatistics(): array
    {
        return [
            'totalArchives' => $this->archiveRepository->countByStatus('active'),
            'expiredArchives' => $this->archiveRepository->countByStatus('expired'),
            'archivedArchives' => $this->archiveRepository->countByStatus('archived'),
            'totalStorageSize' => $this->calculateTotalStorageSize(),
            'formatDistribution' => $this->archiveRepository->getFormatDistribution(),
            'monthlyArchiveCount' => $this->archiveRepository->getMonthlyArchiveCount(),
        ];
    }

    /**
     * 收集学习数据
     */
    private function collectLearningData(string $userId, string $courseId): array
    {
        // 获取学习会话
        $sessions = $this->sessionRepository->findByUserAndCourse($userId, $courseId);
        
        // 获取学习行为
        $behaviors = $this->behaviorRepository->findByUserAndCourse($userId, $courseId);
        
        // 获取异常记录
        $anomalies = $this->anomalyRepository->findByUserAndCourse($userId, $courseId);

        // 会话汇总
        $sessionSummary = [
            'totalSessions' => count($sessions),
            'totalTime' => array_sum(array_map(fn($s) => $s->getTotalDuration(), $sessions)),
            'completionRate' => $this->calculateCompletionRate($sessions),
            'averageSessionTime' => count($sessions) > 0 ? array_sum(array_map(fn($s) => $s->getTotalDuration(), $sessions)) / count($sessions) : 0,
            'firstLearnTime' => ($sessions !== null) ? min(array_map(fn($s) => $s->getFirstLearnTime(), $sessions))->format('Y-m-d H:i:s') : null,
            'lastLearnTime' => ($sessions !== null) ? max(array_map(fn($s) => $s->getLastLearnTime(), $sessions))->format('Y-m-d H:i:s') : null,
        ];

        // 行为汇总
        $behaviorSummary = [
            'totalBehaviors' => count($behaviors),
            'behaviorStats' => $this->calculateBehaviorStats($behaviors),
            'suspiciousCount' => count(array_filter($behaviors, fn($b) => $b->isSuspicious())),
            'mostCommonBehavior' => $this->getMostCommonBehavior($behaviors),
        ];

        // 异常汇总
        $anomalySummary = [
            'totalAnomalies' => count($anomalies),
            'anomalyTypes' => $this->getAnomalyTypeDistribution($anomalies),
            'resolutionStats' => $this->getAnomalyResolutionStats($anomalies),
            'severityDistribution' => $this->getAnomalySeverityDistribution($anomalies),
        ];

        return [
            'sessionSummary' => $sessionSummary,
            'behaviorSummary' => $behaviorSummary,
            'anomalySummary' => $anomalySummary,
            'totalEffectiveTime' => $this->calculateTotalEffectiveTime($sessions),
            'totalSessions' => count($sessions),
            'archiveGeneratedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 生成档案文件
     */
    private function generateArchiveFile(string $userId, string $courseId, array $data, string $format): string
    {
        $filename = sprintf(
            'learn_archive_%s_%s_%s.%s',
            $userId,
            $courseId,
            date('Y_m_d_H_i_s'),
            $format
        );

        $filepath = $this->archiveStoragePath . '/' . $filename;

        // 确保目录存在
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = match ($format) {
            self::ARCHIVE_FORMAT_JSON => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            self::ARCHIVE_FORMAT_XML => $this->generateXmlContent($data),
            self::ARCHIVE_FORMAT_PDF => $this->generatePdfContent($data),
            default => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        };

        file_put_contents($filepath, $content);

        return $filepath;
    }

    /**
     * 计算文件哈希
     */
    private function calculateFileHash(string $filepath): string
    {
        return hash_file('sha256', $filepath);
    }

    /**
     * 计算完成率
     */
    private function calculateCompletionRate(array $sessions): float
    {
        if (empty($sessions)) {
            return 0.0;
        }

        $completedSessions = array_filter($sessions, fn($s) => $s->isFinished());
        return (count($completedSessions) / count($sessions)) * 100;
    }

    /**
     * 计算行为统计
     */
    private function calculateBehaviorStats(array $behaviors): array
    {
        $stats = [];
        foreach ($behaviors as $behavior) {
            $type = $behavior->getBehaviorType();
            $stats[$type] = ($stats[$type]) + 1;
        }
        return $stats;
    }

    /**
     * 获取最常见行为
     */
    private function getMostCommonBehavior(array $behaviors): ?string
    {
        $stats = $this->calculateBehaviorStats($behaviors);
        if (empty($stats)) {
            return null;
        }
        return array_key_first(array_slice(arsort($stats) ? $stats : [], 0, 1, true));
    }

    /**
     * 获取异常类型分布
     */
    private function getAnomalyTypeDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $type = $anomaly->getAnomalyType()->value;
            $distribution[$type] = ($distribution[$type]) + 1;
        }
        return $distribution;
    }

    /**
     * 获取异常解决统计
     */
    private function getAnomalyResolutionStats(array $anomalies): array
    {
        $stats = ['resolved' => 0, 'pending' => 0, 'ignored' => 0];
        foreach ($anomalies as $anomaly) {
            $status = $anomaly->getStatus();
            if ($status === 'resolved') {
                $stats['resolved']++;
            } elseif ($status === 'ignored') {
                $stats['ignored']++;
            } else {
                $stats['pending']++;
            }
        }
        return $stats;
    }

    /**
     * 获取异常严重程度分布
     */
    private function getAnomalySeverityDistribution(array $anomalies): array
    {
        $distribution = [];
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly->getSeverity()->value;
            $distribution[$severity] = ($distribution[$severity]) + 1;
        }
        return $distribution;
    }

    /**
     * 计算总有效时长
     */
    private function calculateTotalEffectiveTime(array $sessions): float
    {
        // 这里需要结合LearnProgressService来计算有效时长
        // 简化实现，直接使用会话总时长
        return array_sum(array_map(fn($s) => $s->getTotalDuration(), $sessions));
    }

    /**
     * 计算总存储大小
     */
    private function calculateTotalStorageSize(): int
    {
        $archives = $this->archiveRepository->findAll();
        $totalSize = 0;

        foreach ($archives as $archive) {
            if (file_exists($archive->getArchivePath())) {
                $totalSize += filesize($archive->getArchivePath());
            }
        }

        return $totalSize;
    }

    /**
     * 解析XML内容
     */
    private function parseXmlContent(string $content): array
    {
        $xml = simplexml_load_string($content);
        return json_decode(json_encode($xml), true);
    }

    /**
     * 生成XML内容
     */
    private function generateXmlContent(array $data): string
    {
        $xml = new \SimpleXMLElement('<learnArchive/>');
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }

    /**
     * 数组转XML
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * 生成PDF内容
     */
    private function generatePdfContent(array $data): string
    {
        // 简化实现，实际应该使用PDF库如TCPDF或DomPDF
        $content = "学习档案报告\n";
        $content .= "生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "会话汇总:\n";
        $content .= json_encode($data['sessionSummary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $content .= "\n\n行为汇总:\n";
        $content .= json_encode($data['behaviorSummary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $content .= "\n\n异常汇总:\n";
        $content .= json_encode($data['anomalySummary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $content;
    }
} 