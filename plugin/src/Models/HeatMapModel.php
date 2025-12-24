<?php

namespace blfilme\lostplaces\Models;

class HeatMapModel
{
    public function __construct(
        private CoordinateModel $coordinate,
        private int $weight,
    ) {
    }

    public function getCoordinate(): CoordinateModel
    {
        return $this->coordinate;
    }

    public function setCoordinate(CoordinateModel $coordinate): void
    {
        $this->coordinate = $coordinate;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function toArray(): array
    {
        return [$this->coordinate->getLatitude(), $this->coordinate->getLongitude(), $this->weight];
    }
}
