<?php

use crisp\Controllers\EventController;
use crisp\core\Logger;
use Crispy\Events\PluginActivatedEvent;
use helloworld\TestEventSubscriber;

include_once __DIR__ . '/vendor/autoload.php';

class Plugin
{
    public function __construct()
    {
        EventController::getEventDispatcher()->addSubscriber(new TestEventSubscriber());
    }
}