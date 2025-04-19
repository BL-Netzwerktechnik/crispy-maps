<?php

namespace Crispy\Enums;

enum AccessTokens: int
{
    case REGISTER = 0x1;
    case PASSWORD_RESET = 0x2;
    case EMAIL_CONFIRMATION = 0x4;
}
