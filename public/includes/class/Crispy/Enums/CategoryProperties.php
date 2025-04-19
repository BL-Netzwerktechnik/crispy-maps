<?php

namespace Crispy\Enums;

enum CategoryProperties: int
{
    case VISIBILITY_PUBLIC = 0x1;
    case VISIBILITY_PRIVATE = 0x2;
    

    public function hasProperty(int $needle): bool
    {
        return ($this->value & $needle);
    }


}
