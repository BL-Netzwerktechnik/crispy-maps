<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Interfaces\CategoryInterface;
use blfilme\lostplaces\Interfaces\CoordinateInterface;

class CoordinateModel implements CoordinateInterface
{
    public function __construct(
        private float $latitude,
        private float $longitude,
    ) {}

    /**
     * Get the latitude of the coordinate
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Get the longitude of the coordinate
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function toDecimal(): string
    {
        return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
    }

    public function toDMS(): string
    {
        return $this->convertToDMS($this->latitude, 'lat') . ' ' . $this->convertToDMS($this->longitude, 'lon');
    }

    private function convertToDMS(float $dec, string $type): string
    {
        $direction = $type === 'lat'
            ? ($dec >= 0 ? 'N' : 'S')
            : ($dec >= 0 ? 'E' : 'W');

        $dec = abs($dec);
        $degrees = floor($dec);
        $minutesFloat = ($dec - $degrees) * 60;
        $minutes = floor($minutesFloat);
        $seconds = round(($minutesFloat - $minutes) * 60, 1);

        return sprintf("%d°%d'%0.1f\"%s", $degrees, $minutes, $seconds, $direction);
    }
}
