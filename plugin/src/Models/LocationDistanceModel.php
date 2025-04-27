<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Interfaces\IconInterface;
use Carbon\Carbon;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Models\UserModel;

class LocationDistanceModel
{
    public function __construct(
        public LocationModel $location,
        public float $distance,
    )
    {}

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