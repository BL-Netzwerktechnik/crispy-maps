<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Interfaces\CategoryInterface;
use blfilme\lostplaces\Interfaces\CoordinateInterface;

class PointModel
{
    public function __construct(
        private int $x,
        private int $y,
    ) {}

    /**
     * Get the x coordinate of the point
     *
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Get the y coordinate of the point
     *
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    public function toArray(): array
    {
        return [$this->x, $this->y];
    }
}
