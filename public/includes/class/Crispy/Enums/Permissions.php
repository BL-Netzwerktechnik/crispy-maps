<?php

namespace Crispy\Enums;

enum Permissions: int
{
    /**
     * System Administrator
     */
    case SUPERUSER = 0x1;

    case LOGIN = 0x2;

    //case EDITOR = 0x4; // Unused

    case READ_LAYOUTS = 0x8;
    case WRITE_LAYOUTS = 0x10;

    case READ_TEMPLATES = 0x20;
    case WRITE_TEMPLATES = 0x40;

    case READ_USERS = 0x80;
    case WRITE_USERS = 0x100;

    case READ_ROLES = 0x200;
    case WRITE_ROLES = 0x400;

    case READ_PAGES = 0x800;
    case WRITE_PAGES = 0x1000;

    case READ_NAVBARS = 0x2000;
    case WRITE_NAVBARS = 0x4000;

    case READ_FILES = 0x8000;
    case WRITE_FILES = 0x10000;

    case READ_PLUGINS = 0x20000;
    case WRITE_PLUGINS = 0x40000;

    case READ_CATEGORIES = 0x80000;
    case WRITE_CATEGORIES = 0x100000;
}
