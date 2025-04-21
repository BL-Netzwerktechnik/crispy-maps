<?php

namespace blfilme\lostplaces\Enums;


enum LocationProperties: int
{
    case ACCESS_WITH_PERMISSION = 0x1;
    case CAMERAS_PRESENT = 0x2;
    case FENCED = 0x4;
    case GUARD_PRESENT = 0x8;
    case INACCESSIBLE = 0x10;
    case INHABITED = 0x20;
    case GUARD_WITH_DOG = 0x40;
    case CAMERAS_PRESENT_MAYBE_INACTIVE = 0x80;
    case GUARD_WITH_VEHICLE = 0x100;



    /**
     * Get Label for the enum value in German
     * @todo Add Translation support
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACCESS_WITH_PERMISSION => 'Zugang mit Erlaubnis',
            self::CAMERAS_PRESENT => 'Kameras vorhanden',
            self::FENCED => 'Eingezäunt',
            self::GUARD_PRESENT => 'Wachschutz anwesend',
            self::INACCESSIBLE => 'Unzugänglich',
            self::INHABITED => 'Bewohnt',
            self::GUARD_WITH_DOG => 'Wachschutz mit Hund',
            self::CAMERAS_PRESENT_MAYBE_INACTIVE => 'Kameras vorhanden (vielleicht inaktiv)',
            self::GUARD_WITH_VEHICLE => 'Wachschutz mit Fahrzeug',
        };
    }

    public static function fromIntToArray(int $value): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            if ($case->value & $value) {
                $result[] = $case;
            }
        }
        return $result;
    }

    public static function fromArrayToInt(array $properties): int
    {
        $result = 0;
        foreach ($properties as $property) {
            if ($property instanceof self) {
                $result |= $property->value;
            }
        }
        return $result;
    }
}