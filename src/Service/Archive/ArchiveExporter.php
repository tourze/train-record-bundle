<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Archive;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;

/**
 * 档案导出器
 * 负责档案导出功能
 */
#[WithMonologChannel(channel: 'train_record')]
class ArchiveExporter
{
    public function __construct(
        private readonly LearnArchiveRepository $archiveRepository,
        private readonly LoggerInterface $logger,
        private readonly string $archiveStoragePath,
    ) {
    }

    /**
     * 导出档案为指定格式
     */
    public function exportArchive(string $archiveId, string $format): string
    {
        $archive = $this->findArchiveOrThrow($archiveId);
        $exportPath = $this->buildExportPath($archiveId, $format);

        $content = $this->generateExportContent($archive, $format);

        $this->ensureDirectoryExists($exportPath);
        file_put_contents($exportPath, $content);

        $this->logExportCompletion($archiveId, $format, $exportPath);

        return $exportPath;
    }

    /**
     * 查找档案或抛出异常
     */
    private function findArchiveOrThrow(string $archiveId): LearnArchive
    {
        $archive = $this->archiveRepository->find($archiveId);
        if (null === $archive) {
            throw new ArgumentException('档案不存在');
        }

        return $archive;
    }

    /**
     * 构建导出路径
     */
    private function buildExportPath(string $archiveId, string $format): string
    {
        return $this->archiveStoragePath . DIRECTORY_SEPARATOR . 'export_' . $archiveId . '_' . time() . '.' . $format;
    }

    /**
     * 生成导出内容
     */
    private function generateExportContent(LearnArchive $archive, string $format): string
    {
        return match ($format) {
            'json' => $this->exportAsJson($archive),
            'xml' => $this->exportAsXml($archive),
            'pdf' => $this->exportAsPdf($archive),
            default => throw new ArgumentException('不支持的导出格式: ' . $format),
        };
    }

    /**
     * 记录导出完成
     */
    private function logExportCompletion(string $archiveId, string $format, string $exportPath): void
    {
        $this->logger->info('档案已导出', [
            'archiveId' => $archiveId,
            'format' => $format,
            'exportPath' => $exportPath,
        ]);
    }

    /**
     * 确保目录存在
     */
    private function ensureDirectoryExists(string $filepath): void
    {
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    /**
     * 导出为JSON格式
     */
    private function exportAsJson(LearnArchive $archive): string
    {
        $data = [
            'id' => $archive->getId(),
            'userId' => $archive->getUserId(),
            'courseId' => $archive->getCourse()->getId(),
            'archiveTime' => $archive->getArchiveTime()?->format('Y-m-d H:i:s'),
            'expiryTime' => $archive->getExpiryTime()?->format('Y-m-d H:i:s'),
            'status' => $archive->getArchiveStatus()->value,
            'format' => $archive->getArchiveFormat()->value,
            'sessionSummary' => $archive->getSessionSummary(),
            'behaviorSummary' => $archive->getBehaviorSummary(),
            'anomalySummary' => $archive->getAnomalySummary(),
            'totalEffectiveTime' => $archive->getTotalEffectiveTime(),
            'totalSessions' => $archive->getTotalSessions(),
        ];

        $result = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (false === $result) {
            throw new \RuntimeException('JSON 编码失败');
        }

        return $result;
    }

    /**
     * 导出为XML格式
     */
    private function exportAsXml(LearnArchive $archive): string
    {
        $xml = new \SimpleXMLElement('<archive/>');
        $xml->addChild('id', $archive->getId());
        $xml->addChild('userId', $archive->getUserId());
        $xml->addChild('courseId', (string) $archive->getCourse()->getId());
        $xml->addChild('archiveTime', $archive->getArchiveTime()?->format('Y-m-d H:i:s'));
        $xml->addChild('expiryTime', $archive->getExpiryTime()?->format('Y-m-d H:i:s'));
        $xml->addChild('status', (string) $archive->getArchiveStatus()->value);
        $xml->addChild('format', (string) $archive->getArchiveFormat()->value);
        $xml->addChild('totalEffectiveTime', (string) $archive->getTotalEffectiveTime());
        $xml->addChild('totalSessions', (string) $archive->getTotalSessions());

        $result = $xml->asXML();

        return false !== $result ? $result : '';
    }

    /**
     * 导出为PDF格式（简化版本）
     */
    private function exportAsPdf(LearnArchive $archive): string
    {
        $content = "学习档案导出\n";
        $content .= "==================\n";
        $content .= '档案ID: ' . $archive->getId() . "\n";
        $content .= '用户ID: ' . $archive->getUserId() . "\n";
        $content .= '课程ID: ' . $archive->getCourse()->getId() . "\n";
        $content .= '归档时间: ' . $archive->getArchiveTime()?->format('Y-m-d H:i:s') . "\n";
        $content .= '过期时间: ' . $archive->getExpiryTime()?->format('Y-m-d H:i:s') . "\n";
        $content .= '总有效时长: ' . $archive->getTotalEffectiveTime() . " 秒\n";
        $content .= '总会话数: ' . $archive->getTotalSessions() . "\n";

        return $content;
    }
}
