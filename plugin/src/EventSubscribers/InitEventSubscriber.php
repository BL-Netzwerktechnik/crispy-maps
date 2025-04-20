<?php

namespace blfilme\lostplaces\EventSubscribers;

use blfilme\lostplaces\PageControllers\CmsControl\MapPageController;
use crisp\api\Config;
use crisp\api\Helper as ApiHelper;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Router;
use crisp\core\Themes;
use crisp\Events\MigrationEvents;
use crisp\Events\ThemeEvents;
use crisp\types\RouteType;
use Crispy\Events\NavbarCreatedEvent;
use Crispy\Events\PluginActivatedEvent;
use Crispy\Helper;
use Crispy\Models\NavBarItemModel;
use Phroute\Phroute\Route;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InitEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            MigrationEvents::THEME_MIGRATIONS_FINISHED => 'migrate',
            PluginActivatedEvent::class => 'migrate',
            ThemeEvents::SETUP => 'onSetup'
        ];
    }

    public function migrate(Event $event): void
    {

        $Migrations = new Migrations();

        //$Migrations->migrate(__DIR__ . '/../', 'bl-filme/lost-places');

        if (!Config::exists("LostPlaces_MapPath") || empty(Config::get("LostPlaces_MapPath"))) {
            Config::set("LostPlaces_MapPath", "/map");
        }

        if (!Config::exists("LostPlaces_IconClass") || empty(Config::get("LostPlaces_IconClass"))) {
            Config::set("LostPlaces_IconClass", "FontAwesomeSolidIconModel");
        }
    }

    public function onSetup(Event $event)
    {
        Themes::addRendererDirectory("/plugins");
        Router::add(route: "/admin/map", routeType: RouteType::PUBLIC, class: MapPageController::class, method: Route::GET);
    }
}
