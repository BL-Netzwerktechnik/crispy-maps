<?php

namespace blfilme\lostplaces\Enums;


enum AuditAction: int
{
    case CREATED = 0x1;
    case UPDATED = 0x2;
    case DELETED = 0x3;
    


    /**
     * Get Label for the enum value in German
     * @todo Add Translation support
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => 'Erstellt',
            self::UPDATED => 'Aktualisiert',
            self::DELETED => 'Gelöscht'
        };
    }
}