<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Enums\MarkerColors;
use blfilme\lostplaces\Interfaces\IconInterface;
use Carbon\Carbon;

class CategoryModel
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $description,
        private IconInterface $icon,
        private Carbon $createdAt,
        private Carbon $updatedAt,
    ) {}

    /**
     * Get the ID of the category
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return IconInterface
     */
    public function getIcon(): IconInterface
    {
        return $this->icon;
    }


    /**
     * Set the name of the category
     * 
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the description of the category
     * 
     * @param string $description
     * @return self
     */

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the icon of the category
     * 
     * @param IconInterface $icon
     * @return self
     */
    public function setIcon(IconInterface $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Convert the category to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon()->toArray(),
        ];
    }

    /**
     * Get the created at date of the category
     *
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the updated at date of the category
     *
     * @return Carbon
     */

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): self
    {
        $this->updatedAt = Carbon::now($_ENV["TZ"] ?? 'UTC');
        return $this;
    }
}
