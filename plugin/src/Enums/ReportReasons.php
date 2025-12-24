<?php

namespace blfilme\lostplaces\Enums;

enum ReportReasons: int
{
    case INVALID_DETAILS = 0x1;
    case INVALID_LOCATION = 0x2;
    case NETZDG_VIOLATION = 0x4;
    case LOCATION_CLOSED = 0x8;
    case MISCELLANEOUS = 0x10;
    case INAPPROPRIATE_CONTENT = 0x20;

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
            self::INVALID_DETAILS => 'Falsche Angaben',
            self::INVALID_LOCATION => 'Falscher Standort',
            self::NETZDG_VIOLATION => 'Verstoß gemäß § 3 NetzDG',
            self::LOCATION_CLOSED => 'Lost Place nicht mehr vorhanden',
            self::MISCELLANEOUS => 'Sonstiges',
            self::INAPPROPRIATE_CONTENT => 'Unangemessener Inhalt'
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
