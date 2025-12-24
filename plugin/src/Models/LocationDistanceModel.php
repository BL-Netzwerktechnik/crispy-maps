<?php

namespace blfilme\lostplaces\Models;

class LocationDistanceModel
{
    public function __construct(
        public LocationModel $location,
        public float $distance,
    ) {
    }

    public function getLocation(): LocationModel
    {
        return $this->location;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function toArray(): array
    {
        return [
            'location' => $this->location->toArray(),
            'distance' => $this->distance,
        ];
    }
}
