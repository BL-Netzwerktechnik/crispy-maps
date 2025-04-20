<?php

namespace blfilme\lostplaces\Interfaces;

interface CoordinateInterface
{
    /**
     * Get the latitude of the coordinate
     *
     * @return float
     */
    public function getLatitude(): float;

    /**
     * Get the longitude of the coordinate
     *
     * @return float
     */
    public function getLongitude(): float;

    /**
     * Set the latitude of the coordinate
     *
     * @param float $latitude
     * @return void
     */
    public function setLatitude(float $latitude): void;

    /**
     * Set the longitude of the coordinate
     *
     * @param float $longitude
     * @return void
     */
    public function setLongitude(float $longitude): void;

    /**
     * Convert the coordinate to an array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Convert the coordinate to a decimal string
     *
     * @return string
     */
    public function toDecimal(): string;

    /**
     * Convert the coordinate to a DMS string
     *
     * @return string
     */
    public function toDMS(): string;
}