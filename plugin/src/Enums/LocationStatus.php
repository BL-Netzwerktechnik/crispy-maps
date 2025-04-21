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
     * @return MarkerColors
     */
    public function getColor(): MarkerColors
    {
        return match ($this) {
            self::ACTIVE => MarkerColors::GREEN, // Green
            self::DEMOLISHED => MarkerColors::RED, // Red
            self::UNKNOWN => MarkerColors::ORANGE // Orange
        };
    }
}