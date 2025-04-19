<?php

namespace helloworld;

use crisp\api\Helper as ApiHelper;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\Events\MigrationEvents;
use Crispy\Events\NavbarCreatedEvent;
use Crispy\Events\PluginActivatedEvent;
use Crispy\Helper;
use Crispy\Models\NavBarItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TestEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            NavbarCreatedEvent::class => 'onTest',
            MigrationEvents::THEME_MIGRATIONS_FINISHED => 'migrate',
            PluginActivatedEvent::class => 'migrate',
        ];
    }
    
    public function migrate(Event $event): void
    {

        $Migrations = new Migrations();

        $Migrations->migrate(__DIR__ . '/../', 'hello-world');
    }

    public function onTest(NavbarCreatedEvent $event): void
    {
        $event->getNavbar()->addItems(
            new \Crispy\Models\NavBarItemModel(
                name: 'Test',
                url: "test",
                icon: 'fas fa-cogs',
                permissions: [],
            ),
        );
    }
}
