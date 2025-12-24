<?php

namespace blfilme\lostplaces\Interfaces;

interface PointInterface
{
    public function getX(): int;

    public function getY(): int;

    public function toArray(): array;
}
