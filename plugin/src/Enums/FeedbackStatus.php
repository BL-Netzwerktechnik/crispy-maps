<?php

namespace blfilme\lostplaces\Enums;

enum FeedbackStatus: int
{
    case MISCELLANEOUS = 0x0;
    case MAP_INACCURATE = 0x1;
    case NAME_INCORRECT = 0x2;
    case NO_LONGER_EXISTS = 0x3;
    case LABELS_INCORRECT = 0x4;
    case CATEGORY_INCORRECT = 0x5;
    case SECURITY_PRESENCE = 0x6;

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
            self::MISCELLANEOUS => 'Sonstiges',
            self::MAP_INACCURATE => 'Kartenfehler',
            self::NAME_INCORRECT => 'Name falsch',
            self::NO_LONGER_EXISTS => 'Existiert nicht mehr',
            self::LABELS_INCORRECT => 'Anmerkungen falsch',
            self::CATEGORY_INCORRECT => 'Kategorie falsch',
            self::SECURITY_PRESENCE => 'Sicherheitsdienst vorhanden'
        };
    }
}
