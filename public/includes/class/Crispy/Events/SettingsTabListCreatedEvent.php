<?php

namespace Crispy\Events;

use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\SettingsTabListModel;
use Symfony\Contracts\EventDispatcher\Event;


final class SettingsTabListCreatedEvent extends Event
{
    public function __construct(private SettingsTabListModel $settingsTabListModel) {}

    public function getTabList(): SettingsTabListModel
    {
        return $this->settingsTabListModel;
    }
}