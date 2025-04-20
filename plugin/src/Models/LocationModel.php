<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Interfaces\IconInterface;
use blfilme\lostplaces\Interfaces\LocationInterface;
use Carbon\Carbon;

class LocationModel implements LocationInterface
{
    /**
     * LocationModel constructor.
     *
     * @param int|null $id
     * @param string $name 
     * @param string $description
     * @param LocationProperties[] $properties
     * @param LocationStatus $status
     * @param CoordinateModel $coordinates
     * @param int $author
     * @param Carbon $createdAt
     * @param Carbon $updatedAt
     */
    public function __construct(
        public ?int $id,
        private string $name,
        private string $description,
        private array $properties,
        private LocationStatus $status,
        private CoordinateModel $coordinates,
        private int $author,
        private Carbon $createdAt,
        private Carbon $updatedAt,
    )
    {
        // Ensure properties are of type LocationProperties
        foreach ($this->properties as $property) {
            if (!$property instanceof LocationProperties) {
                throw new \InvalidArgumentException('Invalid property type');
            }
        }
    }



}