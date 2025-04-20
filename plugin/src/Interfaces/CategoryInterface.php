<?php

namespace blfilme\lostplaces\Interfaces;

interface CategoryInterface
{
    /**
     * Get the name of the category
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the category
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the icon of the category
     *
     *
     * @return string
     */
    public function getIcon(): IconInterface;
}