<?php

namespace Crispy\Enums;

enum PageProperties: int
{
    case OPTION_FRONTPAGE = 0x1;
    case OPTION_NOT_FOUND = 0x2;

    case VISIBILITY_PUBLIC = 0x4;
    case VISIBILITY_PRIVATE = 0x8;
    

    public function hasProperty(int $needle): bool
    {
        return ($this->value & $needle);
    }


}
