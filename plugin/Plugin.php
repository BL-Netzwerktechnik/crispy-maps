<?php

use crisp\Controllers\EventController;
use crisp\core\Logger;
use Crispy\Events\PluginActivatedEvent;
use blfilme\lostplaces\EventSubscribers\NavbarEventSubscriber;
use blfilme\lostplaces\EventSubscribers\SettingTabListCreatedEventSubscriber;
use blfilme\lostplaces\EventSubscribers\InitEventSubscriber;

include_once __DIR__ . '/vendor/autoload.php';

class Plugin
{
    public function __construct()
    {
        EventController::getEventDispatcher()->addSubscriber(new NavbarEventSubscriber());
        EventController::getEventDispatcher()->addSubscriber(new SettingTabListCreatedEventSubscriber());
        EventController::getEventDispatcher()->addSubscriber(new InitEventSubscriber());
    }
}