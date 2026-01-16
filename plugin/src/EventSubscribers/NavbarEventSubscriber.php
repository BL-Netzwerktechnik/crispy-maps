<?php

namespace blfilme\lostplaces\EventSubscribers;

use crisp\core\Sessions;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\Events\NavbarCreatedEvent;
use Crispy\Models\NavBarItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

    public function onNavbarCreated(NavbarCreatedEvent $event): void
    {

        $user = Sessions::isSessionValid() ? $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']['user']) : false;

        if ($user && $user->getRole()->getId() == 3 && str_starts_with($_SERVER['REQUEST_URI'], '/admin') && !preg_match('#^/admin/(register|login)#', $_SERVER['REQUEST_URI'])) {
            header('Location: /');

            return;
        }

        $event->getNavbar()->addItemsAfter(
            'files',
            new \Crispy\Models\NavbarDividerModel(
                title: 'Crispy Maps',
                permissions: [
                    Permissions::READ_PAGES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::READ_PAGES->value,
                ],
                permissionHaystack: $user->getPermissionArray()
            ),
            new NavBarItemModel(
                name: 'Karte',
                url: 'admin/map',
                icon: 'fa-sharp-duotone fa-regular fa-map-location-dot',
                permissions: [
                    Permissions::READ_PAGES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::WRITE_PAGES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                id: 'lostplaces_map',
            ),
            new NavBarItemModel(
                name: 'Kategorien',
                url: 'admin/lp/categories',
                icon: 'fa-sharp-duotone fa-regular fa-location-dot',
                permissions: [
                    Permissions::READ_CATEGORIES->value,
                    Permissions::SUPERUSER->value,
                    Permissions::WRITE_CATEGORIES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                id: 'lostplaces_categories',
            ),
            new NavBarItemModel(
                name: 'Meldungen',
                url: 'admin/lp/reports',
                icon: 'fa-sharp-duotone fa-regular fa-flag',
                permissions: [
                    Permissions::SUPERUSER->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                id: 'lostplaces_reports',
            ),
        );
    }
}
