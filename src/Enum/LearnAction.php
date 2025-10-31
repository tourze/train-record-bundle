<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LearnAction: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case START = 'start';
    case PLAY = 'play';
    case VIDEO_PLAY = 'video_play';
    case PAUSE = 'pause';
    case VIDEO_PAUSE = 'video_pause';
    case WATCH = 'watch';
    case ENDED = 'ended';
    case VIDEO_ENDED = 'video_ended';
    case PRACTICE = 'practice';

    public function getLabel(): string
    {
        return match ($this) {
            self::START => '开始学习',
            self::PLAY => '播放',
            self::VIDEO_PLAY => '视频播放',
            self::PAUSE => '暂停',
            self::VIDEO_PAUSE => '视频暂停',
            self::WATCH => '观看',
            self::ENDED => '看完',
            self::VIDEO_ENDED => '视频结束',
            self::PRACTICE => '练习',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::START => BadgeInterface::PRIMARY,
            self::PLAY => BadgeInterface::INFO,
            self::VIDEO_PLAY => BadgeInterface::INFO,
            self::PAUSE => BadgeInterface::WARNING,
            self::VIDEO_PAUSE => BadgeInterface::WARNING,
            self::WATCH => BadgeInterface::SUCCESS,
            self::ENDED => BadgeInterface::SUCCESS,
            self::VIDEO_ENDED => BadgeInterface::SUCCESS,
            self::PRACTICE => BadgeInterface::PRIMARY,
        };
    }
}
