<?php

namespace blfilme\lostplaces\Enums;

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\Interfaces\IconInterface;

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
    case SUBJECT_TO_CHARGE = 0x200;
    case DRONE_FLYING_ALLOWED = 0x400;
    case DRONE_FLYING_FORBIDDEN = 0x800;
    case VIDEO_FORBIDDEN = 0x1000;
    case VIDEO_ALLOWED = 0x2000;


    /**
     * Bootstrap Badge color for the enum value
     *
     * @return string
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::ACCESS_WITH_PERMISSION => 'primary',
            self::CAMERAS_PRESENT => 'danger',
            self::FENCED => 'warning',
            self::GUARD_PRESENT => 'danger',
            self::INACCESSIBLE => 'dark',
            self::INHABITED => 'warning',
            self::GUARD_WITH_DOG => 'danger',
            self::CAMERAS_PRESENT_MAYBE_INACTIVE => 'warning',
            self::GUARD_WITH_VEHICLE => 'danger',
            self::SUBJECT_TO_CHARGE => 'info',
            self::DRONE_FLYING_ALLOWED => 'success',
            self::DRONE_FLYING_FORBIDDEN => 'danger',
            self::VIDEO_FORBIDDEN => 'danger',
            self::VIDEO_ALLOWED => 'success',
            default => 'secondary',
        };
    }

    public function getBadgeIcon(): IconInterface
    {
        return match ($this){
            self::ACCESS_WITH_PERMISSION => IconProviderController::fetchFromConfig('envelope'),
            self::CAMERAS_PRESENT => IconProviderController::fetchFromConfig('camera'),
            self::FENCED => IconProviderController::fetchFromConfig('xmarks-lines'),
            self::GUARD_PRESENT => IconProviderController::fetchFromConfig('shield'),
            self::INACCESSIBLE => IconProviderController::fetchFromConfig('lock'),
            self::INHABITED => IconProviderController::fetchFromConfig('home'),
            self::GUARD_WITH_DOG => IconProviderController::fetchFromConfig('dog'),
            self::CAMERAS_PRESENT_MAYBE_INACTIVE => IconProviderController::fetchFromConfig('camera-retro'),
            self::GUARD_WITH_VEHICLE => IconProviderController::fetchFromConfig('car'),
            self::SUBJECT_TO_CHARGE => IconProviderController::fetchFromConfig('money-bill'),
            self::DRONE_FLYING_ALLOWED => IconProviderController::fetchFromConfig('plane-circle-check'),
            self::DRONE_FLYING_FORBIDDEN => IconProviderController::fetchFromConfig('plane-circle-xmark'),
            self::VIDEO_FORBIDDEN => IconProviderController::fetchFromConfig('video-slash'),
            self::VIDEO_ALLOWED => IconProviderController::fetchFromConfig('video'),
            default => IconProviderController::fetchFromConfig('question'),
        };
    }

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
            self::SUBJECT_TO_CHARGE => 'Gebührenpflichtig',
            self::DRONE_FLYING_ALLOWED => 'Drohneneinsatz erlaubt',
            self::DRONE_FLYING_FORBIDDEN => 'Drohneneinsatz verboten',
            self::VIDEO_FORBIDDEN => 'Videoaufnahmen verboten',
            self::VIDEO_ALLOWED => 'Videoaufnahmen erlaubt',
            default => 'Unbekannt',
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