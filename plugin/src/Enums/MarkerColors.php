<?php

namespace blfilme\lostplaces\Enums;

enum MarkerColors: string
{
    case RED = 'red';
    case GREEN = 'green';
    case BLUE = 'blue';
    case PURPLE = 'purple';
    case ORANGE = 'orange';

    public function getLabel(): string
    {
        return match ($this) {
            self::RED => 'Rot',
            self::GREEN => 'Grün',
            self::BLUE => 'Blau',
            self::PURPLE => 'Lila',
            self::ORANGE => 'Orange'
        };
    }
}
