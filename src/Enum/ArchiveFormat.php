<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 归档格式枚举
 */
enum ArchiveFormat: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case JSON = 'json';     // JSON格式
    case XML = 'xml';       // XML格式
    case PDF = 'pdf';       // PDF格式
    case ZIP = 'zip';       // ZIP压缩格式
    case CSV = 'csv';       // CSV格式

    /**
     * 获取格式标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::JSON => 'JSON',
            self::XML => 'XML',
            self::PDF => 'PDF',
            self::ZIP => 'ZIP',
            self::CSV => 'CSV',
        };
    }

    /**
     * 获取格式描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::JSON => 'JSON格式，便于程序处理',
            self::XML => 'XML格式，结构化数据',
            self::PDF => 'PDF格式，便于阅读和打印',
            self::ZIP => 'ZIP压缩格式，节省存储空间',
            self::CSV => 'CSV格式，便于数据分析',
        };
    }

    /**
     * 获取文件扩展名
     */
    public function getExtension(): string
    {
        return match ($this) {
            self::JSON => '.json',
            self::XML => '.xml',
            self::PDF => '.pdf',
            self::ZIP => '.zip',
            self::CSV => '.csv',
        };
    }

    /**
     * 获取MIME类型
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::PDF => 'application/pdf',
            self::ZIP => 'application/zip',
            self::CSV => 'text/csv',
        };
    }

    /**
     * 检查是否支持压缩
     */
    public function supportsCompression(): bool
    {
        return in_array($this, [self::JSON, self::XML, self::ZIP, self::CSV], true);
    }

    /**
     * 检查是否为二进制格式
     */
    public function isBinary(): bool
    {
        return in_array($this, [self::PDF, self::ZIP], true);
    }

    /**
     * 检查是否为文本格式
     */
    public function isText(): bool
    {
        return in_array($this, [self::JSON, self::XML, self::CSV], true);
    }

    /**
     * 获取推荐的压缩级别
     */
    public function getRecommendedCompressionLevel(): int
    {
        return match ($this) {
            self::JSON, self::XML, self::CSV => 6,  // 中等压缩
            self::PDF => 3,                         // 轻度压缩
            self::ZIP => 9,                         // 最大压缩
        };
    }

    /**
     * 获取所有格式
     * @return array<int, self>
     */
    public static function getAllFormats(): array
    {
        return [
            self::JSON,
            self::XML,
            self::PDF,
            self::ZIP,
            self::CSV,
        ];
    }

    /**
     * 获取文本格式
     * @return array<int, self>
     */
    public static function getTextFormats(): array
    {
        return [
            self::JSON,
            self::XML,
            self::CSV,
        ];
    }

    /**
     * 获取二进制格式
     * @return array<int, self>
     */
    public static function getBinaryFormats(): array
    {
        return [
            self::PDF,
            self::ZIP,
        ];
    }

    /**
     * 从文件扩展名创建
     */
    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower($extension);
        if (str_starts_with($extension, '.')) {
            $extension = substr($extension, 1);
        }

        return match ($extension) {
            'json' => self::JSON,
            'xml' => self::XML,
            'pdf' => self::PDF,
            'zip' => self::ZIP,
            'csv' => self::CSV,
            default => null,
        };
    }

    /**
     * 获取Badge样式
     */
    public function getBadge(): string
    {
        return $this->getLabel();
    }

    /**
     * 获取Badge样式类
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::JSON => 'badge-success',
            self::XML => 'badge-info',
            self::PDF => 'badge-danger',
            self::ZIP => 'badge-warning',
            self::CSV => 'badge-primary',
        };
    }
}
