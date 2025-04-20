<?php

namespace blfilme\lostplaces\Enums;


enum LocationStatus: int
{
    
    case ACTIVE = 0x1;
    case DEMOLISHED = 0x2;
    case UNKNOWN = 0x3;


    /**
     * Get Label for the enum value in German
     * @todo Add Translation support
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktiv',
            self::DEMOLISHED => 'Abgerissen',
            self::UNKNOWN => 'Unbekannt'
        };
    }

    /**
     * Gets the color for the enum value in hex format
     *
     * @return string
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => '#00FF00', // Green
            self::DEMOLISHED => '#FF0000', // Red
            self::UNKNOWN => '#FFFF00' // Yellow
        };
    }
}