<?php

namespace Tourze\TrainRecordBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 课时学习状态
 */
enum LessonLearnStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case NOT_BUY = 'not-buy';
    case PENDING = 'pending';
    case LEARNING = 'learning';
    case FINISHED = 'finished';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_BUY => '未购买',
            self::PENDING => '未开始',
            self::LEARNING => '学习中',
            self::FINISHED => '已完成',
        };
    }
}
