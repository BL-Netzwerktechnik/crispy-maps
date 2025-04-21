<?php

namespace blfilme\lostplaces\EventSubscribers;

use blfilme\lostplaces\PageControllers\CmsControl\MapPageController;
use blfilme\lostplaces\PageControllers\MapPageController as PageControllersMapPageController;
use blfilme\lostplaces\PageControllers\Public\ConfigJsonPageController;
use blfilme\lostplaces\PageControllers\Public\MapJsonPageController;
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

        $Migrations->migrate(__DIR__ . '/../../', 'bl-filme/lost-places');

        if (!Config::exists("LostPlaces_MapPath") || empty(Config::get("LostPlaces_MapPath"))) {
            Config::set("LostPlaces_MapPath", "/map.json");
        }

        if (!Config::exists("LostPlaces_IconClass") || empty(Config::get("LostPlaces_IconClass"))) {
            Config::set("LostPlaces_IconClass", "\blfilme\lostplaces\Models\IconModels\FontAwesomeSolidIconModel");
        }

        if (!Config::exists("LostPlaces_ProviderPath") || empty(Config::get("LostPlaces_ProviderPath"))) {
            Config::set("LostPlaces_ProviderPath", "uploads");
        }

        if (!Config::exists("LostPlaces_FileProvider") || empty(Config::get("LostPlaces_FileProvider"))) {
            Config::set("LostPlaces_FileProvider", "LocalFileProvider");
        }

        if (!Config::exists("LostPlaces_MapAttribution") || empty(Config::get("LostPlaces_MapAttribution"))) {
            Config::set("LostPlaces_MapAttribution", '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> Contributors');
        }

        if (!Config::exists("LostPlaces_MapTileServer") || empty(Config::get("LostPlaces_MapTileServer"))) {
            Config::set("LostPlaces_MapTileServer", "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png");
        }
    }

    public function onSetup(Event $event)
    {
        Themes::addRendererDirectory("/plugins");
        Router::add(route: "/admin/map", routeType: RouteType::PUBLIC, class: MapPageController::class, method: Route::GET);
        Router::add(route: "/config.json", routeType: RouteType::PUBLIC, class: ConfigJsonPageController::class, method: Route::GET);
        Router::add(route: Config::get("LostPlaces_MapPath"), routeType: RouteType::PUBLIC, class: MapJsonPageController::class, method: Route::GET);
    }
}
