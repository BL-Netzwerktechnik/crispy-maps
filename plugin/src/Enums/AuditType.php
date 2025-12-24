<?php

namespace blfilme\lostplaces\Enums;

enum AuditType: int
{
    case LOCATION = 0x0;
    case CATEGORY = 0x1;
    case REPORT = 0x2;

    /**
     * Get Label for the enum value in German.
     *
     * @todo Add Translation support
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOCATION => 'Standort',
            self::CATEGORY => 'Kategorie',
            self::REPORT => 'Bericht'
        };
    }
}
