<?php

namespace Crispy\Events;

use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\PluginModel;
use Symfony\Contracts\EventDispatcher\Event;


final class PluginDeactivatedEvent extends Event
{
    public function __construct(private PluginModel $pluginModel) {}

    public function getPlugin(): PluginModel
    {
        return $this->pluginModel;
    }
}