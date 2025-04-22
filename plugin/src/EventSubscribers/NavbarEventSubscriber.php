<?php

namespace blfilme\lostplaces\EventSubscribers;

use crisp\api\Helper as ApiHelper;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Sessions;
use crisp\Events\MigrationEvents;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\Events\NavbarCreatedEvent;
use Crispy\Events\PluginActivatedEvent;
use Crispy\Helper;
use Crispy\Models\NavBarItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class NavbarEventSubscriber implements EventSubscriberInterface
{


    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }



    public static function getSubscribedEvents(): array
    {
        return [
            NavbarCreatedEvent::class => 'onNavbarCreated',
        ];
    }

    public function migrate(Event $event): void
    {

        $Migrations = new Migrations();

        //$Migrations->migrate(__DIR__ . '/../', 'bl-filme/lost-places');
    }

    public function onNavbarCreated(NavbarCreatedEvent $event): void
    {

        $user = Sessions::isSessionValid() ? $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']["user"]) : false;

        
        $event->getNavbar()->addItemsAfter(
            "files",
            new \Crispy\Models\NavbarDividerModel(
                title: 'Lost Places',
                permissions: [
                    Permissions::READ_PAGES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::READ_PAGES->value
                ],
                permissionHaystack: $user->getPermissionArray()
            ),
            new \Crispy\Models\NavBarItemModel(
                name: 'Karte',
                url: "admin/map",
                icon: 'fas fa-map-location-dot',
                permissions: [
                    Permissions::READ_PAGES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::WRITE_PAGES->value
                ],
                permissionHaystack: $user->getPermissionArray(),
                id: 'lostplaces_map',
            ),
            new \Crispy\Models\NavBarItemModel(
                name: 'Kategorien',
                url: "admin/lp/categories",
                icon: 'fas fa-location-dot',
                permissions: [
                    Permissions::READ_CATEGORIES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::WRITE_CATEGORIES->value
                ],
                permissionHaystack: $user->toArray(),
                id: 'lostplaces_categories',
            ),
        );
    }
}
