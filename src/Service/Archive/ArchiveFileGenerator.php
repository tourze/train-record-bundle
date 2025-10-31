<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service\Archive;

/**
 * 档案文件生成器
 * 负责生成各种格式的档案文件
 */
class ArchiveFileGenerator
{
    // 归档格式常量
    private const ARCHIVE_FORMAT_JSON = 'json';
    private const ARCHIVE_FORMAT_XML = 'xml';
    private const ARCHIVE_FORMAT_PDF = 'pdf';
    private const ARCHIVE_FORMAT_CSV = 'csv';

    public function __construct(
        private readonly string $archiveStoragePath,
    ) {
    }

    /**
     * 生成档案文件
     * @param array<string, mixed> $data
     */
    public function generateArchiveFile(string $userId, string $courseId, array $data, string $format): string
    {
        $filepath = $this->buildArchiveFilePath($userId, $courseId, $format);
        $this->ensureDirectoryExists($filepath);

        $content = $this->generateContentByFormat($data, $format);
        file_put_contents($filepath, $content);

        return $filepath;
    }

    /**
     * 计算文件哈希
     */
    public function calculateFileHash(string $filepath): string
    {
        $result = hash_file('sha256', $filepath);
        if (false === $result) {
            throw new \RuntimeException('计算文件哈希失败');
        }

        return $result;
    }

    /**
     * 构建档案文件路径
     */
    private function buildArchiveFilePath(string $userId, string $courseId, string $format): string
    {
        $filename = sprintf(
            'learn_archive_%s_%s_%s.%s',
            $userId,
            $courseId,
            date('Y_m_d_H_i_s'),
            $format
        );

        return $this->archiveStoragePath . DIRECTORY_SEPARATOR . $filename;
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
     * 根据格式生成内容
     * @param array<string, mixed> $data
     */
    private function generateContentByFormat(array $data, string $format): string
    {
        return match ($format) {
            self::ARCHIVE_FORMAT_JSON => $this->generateJsonContent($data),
            self::ARCHIVE_FORMAT_XML => $this->generateXmlContent($data),
            self::ARCHIVE_FORMAT_PDF => $this->generatePdfContent($data),
            self::ARCHIVE_FORMAT_CSV => $this->generateCsvContent($data),
            default => $this->generateJsonContent($data),
        };
    }

    /**
     * 生成JSON内容
     * @param array<string, mixed> $data
     */
    private function generateJsonContent(array $data): string
    {
        $result = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false === $result ? '' : $result;
    }

    /**
     * 生成XML内容
     * @param array<string, mixed> $data
     */
    private function generateXmlContent(array $data): string
    {
        $xml = new \SimpleXMLElement('<learnArchive/>');
        $this->arrayToXml($data, $xml);

        $result = $xml->asXML();
        if (false === $result) {
            throw new \RuntimeException('XML 生成失败');
        }

        return $result;
    }

    /**
     * 数组转XML
     * @param array<string, mixed> $data
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $key = (string) $key;
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                /** @var array<string, mixed> $value */
                $this->arrayToXml($value, $subnode);
            } else {
                $stringValue = '';
                if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                    $stringValue = (string) $value;
                } elseif (null === $value) {
                    $stringValue = '';
                }
                $xml->addChild($key, htmlspecialchars($stringValue));
            }
        }
    }

    /**
     * 生成PDF内容
     * @param array<string, mixed> $data
     */
    private function generatePdfContent(array $data): string
    {
        $content = "学习档案报告\n";
        $content .= '生成时间: ' . date('Y-m-d H:i:s') . "\n\n";
        $content .= "会话汇总:\n";
        $content .= json_encode($data['sessionSummary'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $content .= "\n\n行为汇总:\n";
        $content .= json_encode($data['behaviorSummary'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $content .= "\n\n异常汇总:\n";
        $content .= json_encode($data['anomalySummary'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $content;
    }

    /**
     * 生成CSV内容
     * @param array<string, mixed> $data
     */
    private function generateCsvContent(array $data): string
    {
        if ([] === $data) {
            return '';
        }

        $output = $this->createTempCsvStream();

        $this->writeCsvData($output, $data);

        return $this->extractCsvContent($output);
    }

    /**
     * @return resource
     */
    private function createTempCsvStream()
    {
        $output = fopen('php://temp', 'r+');
        if (false === $output) {
            throw new \RuntimeException('创建临时文件流失败');
        }

        return $output;
    }

    /**
     * @param resource $output
     * @param array<string, mixed> $data
     */
    private function writeCsvData($output, array $data): void
    {
        if ($this->isArchiveData($data)) {
            $this->writeArchiveCsv($output, $data);
        } else {
            $this->writeGenericCsv($output, $data);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isArchiveData(array $data): bool
    {
        return isset($data['sessionSummary']) || isset($data['behaviorSummary']);
    }

    /**
     * @param resource $output
     * @param array<string, mixed> $data
     */
    private function writeArchiveCsv($output, array $data): void
    {
        $flatData = $this->flattenArchiveData($data);
        fputcsv($output, array_keys($flatData));
        fputcsv($output, array_values($flatData));
    }

    /**
     * @param resource $output
     * @param array<string, mixed> $data
     */
    private function writeGenericCsv($output, array $data): void
    {
        $firstRow = reset($data);
        if (!is_array($firstRow)) {
            return;
        }

        fputcsv($output, array_keys($firstRow));
        foreach ($data as $row) {
            if (is_array($row)) {
                // Ensure row has string keys (array<string, mixed>)
                $typedRow = [];
                foreach ($row as $key => $value) {
                    $typedRow[(string) $key] = $value;
                }
                $stringValues = $this->convertRowToStrings($typedRow);
                fputcsv($output, $stringValues);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string>
     */
    private function convertRowToStrings(array $row): array
    {
        $values = array_values($row);

        return array_map(fn ($value): string => is_scalar($value) || null === $value ? (string) $value : '', $values);
    }

    /**
     * @param resource $output
     */
    private function extractCsvContent($output): string
    {
        rewind($output);
        $csv = stream_get_contents($output);
        if (false === $csv) {
            fclose($output);
            throw new \RuntimeException('读取 CSV 内容失败');
        }
        fclose($output);

        return $csv;
    }

    /**
     * 将档案数据转换为平面数组
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function flattenArchiveData(array $data): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            $flat[$key] = $this->convertValueToString($value);
        }

        return $flat;
    }

    /**
     * @param mixed $value
     */
    private function convertValueToString($value): string
    {
        if (is_array($value)) {
            // Ensure array has string keys for type safety
            $typedArray = [];
            foreach ($value as $key => $val) {
                $typedArray[(string) $key] = $val;
            }

            return $this->encodeArrayToJson($typedArray);
        }

        if (is_scalar($value) || null === $value) {
            return (string) $value;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $value
     */
    private function encodeArrayToJson(array $value): string
    {
        $encoded = json_encode($value);
        if (false === $encoded) {
            throw new \RuntimeException('JSON 编码失败');
        }

        return $encoded;
    }

    /**
     * 解析XML内容
     * @return array<string, mixed>
     */
    public function parseXmlContent(string $content): array
    {
        $xml = simplexml_load_string($content);
        if (false === $xml) {
            throw new \RuntimeException('XML 解析失败');
        }

        $json = json_encode($xml);
        if (false === $json) {
            throw new \RuntimeException('XML 转 JSON 失败');
        }

        $result = json_decode($json, true);
        if (null === $result) {
            throw new \RuntimeException('JSON 解析失败');
        }
        if (!is_array($result)) {
            throw new \RuntimeException('解析结果不是数组格式');
        }

        // Ensure array has string keys for type safety
        $typedResult = [];
        foreach ($result as $key => $value) {
            $typedResult[(string) $key] = $value;
        }

        return $typedResult;
    }

    /**
     * 解析CSV内容
     * @return array<int, array<string, string>>
     */
    public function parseCsvContent(string $content): array
    {
        $rawLines = str_getcsv($content, "\n");
        // 过滤掉 NULL 值（空内容时 str_getcsv 返回 [null]），并确保类型安全
        $lines = array_filter($rawLines, static fn ($line): bool => is_string($line));

        $lineCount = count($lines);
        if (0 === $lineCount) {
            return [];
        }

        // 安全访问数组元素，确保第一个元素存在
        if (!isset($lines[0])) {
            return [];
        }

        $headers = $this->extractCsvHeaders($lines[0]);
        if ([] === $headers) {
            return [];
        }

        return $this->parseCsvDataLines($lines, $headers);
    }

    /**
     * 提取CSV标题行
     * @return array<string>
     */
    private function extractCsvHeaders(string $firstLine): array
    {
        $trimmedLine = trim($firstLine);
        if ('' === $trimmedLine) {
            return [];
        }

        $headers = str_getcsv($firstLine);

        return array_map(static fn ($value): string => (string) $value, $headers);
    }

    /**
     * 解析CSV数据行
     * @param array<string> $lines
     * @param array<string> $headers
     * @return array<int, array<string, string>>
     */
    private function parseCsvDataLines(array $lines, array $headers): array
    {
        $data = [];

        for ($i = 1; $i < count($lines); ++$i) {
            $line = $lines[$i] ?? '';
            $row = str_getcsv($line);

            if (count($row) !== count($headers)) {
                continue;
            }

            $combined = array_combine($headers, $row);
            $data[] = array_map('strval', $combined);
        }

        return $data;
    }
}
