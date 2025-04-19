<?php

namespace Crispy\Enums;

enum NavbarProperties: int
{
    case VISIBILITY_PUBLIC = 0x1;
    case VISIBILITY_PRIVATE = 0x2;

    case TARGET_SELF = 0x4;
    case TARGET_BLANK = 0x8;
    
    

    public function hasProperty(int $needle): bool
    {
        return ($this->value & $needle);
    }


}
