<?php

namespace blfilme\lostplaces\Interfaces;

use blfilme\lostplaces\Enums\LocationStatus;
use Carbon\Carbon;

interface LocationInterface
{

    /**
     * Get the name of the location
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the location
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the coordinates of the location
     *
     * @return CoordinateInterface
     */
    public function getCoordinates(): CoordinateInterface;

    /**
     * Get the category of the location
     *
     * @return CategoryInterface
     */
    public function getCategory(): CategoryInterface;

    /**
     * Get the icon of the location
     *
     * @return IconInterface
     */
    public function getIcon(): IconInterface;

    /**
     * Get the images of the location
     *
     * @return ImageModel[]
     */

    public function getImages(): array;

    public function getImageCount(): int;

    /**
     * Get the properties of the location
     *
     * @return LocationProperties[]
     */
    public function getProperties(): array;

    public function getStatus(): LocationStatus;
    public function getAuthor(): int;
    public function getCreatedAt(): Carbon;
    public function getUpdatedAt(): Carbon;
    public function getId(): ?int;
}
