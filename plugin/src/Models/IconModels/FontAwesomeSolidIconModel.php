<?php

namespace blfilme\lostplaces\Models\IconModels;

use blfilme\lostplaces\Interfaces\IconInterface;

class FontAwesomeSolidIconModel implements IconInterface
{
    public function __construct(
        private string $name,
    ) {}

    /**
     * Get the name of the icon
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the prefix of the icon
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return 'fa-solid';
    }

    /**
     * Get the full class name of the icon, e.g. "fa-solid fa-camera"
     *
     * @return string
     */
    public function getFullClass(): string
    {
        return $this->getPrefix() . ' fa-' . $this->getName();
    }

    public function __toString(): string
    {
        return $this->getFullClass();
    }

    public function toArray(): array
    {
        return [
            'prefix' => $this->getPrefix(),
            'name' => $this->getName(),
            'fullClass' => $this->getFullClass(),
        ];
    }
}
