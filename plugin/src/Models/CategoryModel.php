<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Interfaces\CategoryInterface;
use blfilme\lostplaces\Interfaces\IconInterface;

class CategoryModel implements CategoryInterface
{
    public function __construct(
        private string $name,
        private string $description,
        private IconInterface $icon,
    )
    {
        
    }

    /**
     * Get the name of the category
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the description of the category
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the icon of the category
     *
     * @return string
     */
    public function getIcon(): IconInterface
    {
        return $this->icon;
    }


}