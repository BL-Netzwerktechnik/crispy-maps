<?php

namespace Crispy\Events;

use Crispy\Models\CmsControlNavbarModel;
use Symfony\Contracts\EventDispatcher\Event;


final class NavbarCreatedEvent extends Event
{
    public function __construct(private CmsControlNavbarModel $cmsControlNavbarModel) {}

    public function getNavbar(): CmsControlNavbarModel
    {
        return $this->cmsControlNavbarModel;
    }
}