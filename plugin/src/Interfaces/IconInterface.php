<?php

namespace blfilme\lostplaces\Interfaces;

interface IconInterface
{
    /**
     * Prefix of the icon, e.g. "fa-solid" for Font Awesome
     *
     * @return string
     */
    public function getPrefix(): string;

    /**
     * Name of the icon, e.g. "camera" for Font Awesome
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the full class name of the icon, e.g. "fa-solid fa-camera"
     *
     * @return string
     */
    public function getFullClass(): string;
}