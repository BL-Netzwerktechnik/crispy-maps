<?php

namespace Crispy\Enums;

enum NavbarLinkTypes: int
{
    case CATEGORY_TARGET = 0x1;
    case PAGE_TARGET = 0x2;
    case URL_TARGET = 0x4;
    case NO_TARGET = 0x8;
    
}
