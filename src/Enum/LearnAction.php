<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LearnAction: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case START = 'start';
    case PLAY = 'play';
    case PAUSE = 'pause';
    case WATCH = 'watch';
    case ENDED = 'ended';
    case PRACTICE = 'practice';

    public function getLabel(): string
    {
        return match ($this) {
            self::START => '开始学习',
            self::PLAY => '播放',
            self::PAUSE => '暂停',
            self::WATCH => '观看',
            self::ENDED => '看完',
            self::PRACTICE => '练习',
        };
    }
}
