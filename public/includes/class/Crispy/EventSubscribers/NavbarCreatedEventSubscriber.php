<?php

namespace Crispy\EventSubscribers;

use crisp\api\Helper as ApiHelper;
use crisp\api\Translation;
use crisp\core\Router;
use crisp\Events\ThemeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

use crisp\Controllers\EventController;
use crisp\core\CLI;
use crisp\core\Cron;
use crisp\core\Sessions;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use example\views\controllers\StartPageController;
use crisp\types\RouteType;
use Crispy\CommandControllers\CreateLayoutCommandController;
use Crispy\CommandControllers\GenerateFrontendCommandController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\HrefTargets;
use Crispy\Enums\Permissions;
use Crispy\Events\NavbarCreatedEvent;
use Crispy\Helper;
use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\NavbarDividerModel;
use Crispy\Models\NavBarItemModel;
use Crispy\PageControllers\CmsControl\CategoriesPageController;
use Crispy\PageControllers\CmsControl\CategoryPageController;
use Crispy\PageControllers\CmsControl\CreateLayoutPageController;
use Crispy\PageControllers\CmsControl\CreateTemplatePageController;
use Crispy\PageControllers\CmsControl\DashboardPageController;
use Crispy\PageControllers\CmsControl\EditLayoutPageController;
use Crispy\PageControllers\CmsControl\EditTemplatePageController;
use Crispy\PageControllers\CmsControl\FileManagerConnectorPageController;
use Crispy\PageControllers\CmsControl\FilesPageController;
use Crispy\PageControllers\CmsControl\LayoutsPageController;
use Crispy\PageControllers\CmsControl\LoginPageController;
use Crispy\PageControllers\CmsControl\LogoutPageController;
use Crispy\PageControllers\CmsControl\MoveCategoryPageController;
use Crispy\PageControllers\CmsControl\MovePagePageController;
use Crispy\PageControllers\CmsControl\PagePageController;
use Crispy\PageControllers\CmsControl\PagesPageController;
use Crispy\PageControllers\CmsControl\RegisterPageController;
use Crispy\PageControllers\CmsControl\RolePageController;
use Crispy\PageControllers\CmsControl\RolesPageController;
use Crispy\PageControllers\CmsControl\SettingsPageController;
use Crispy\PageControllers\CmsControl\TemplatesPageController;
use Crispy\PageControllers\CmsControl\UserPageController;
use Crispy\PageControllers\CmsControl\UsersPageController;
use Crispy\PageControllers\RenderPageController;
use Crispy\PageControllers\RenderTranslationJsonPageController;
use Crispy\PageControllers\TestPageController;
use example\views\controllers\ApiPagecontroller;
use example\views\controllers\CronTestController;
use Phroute\Phroute\Route;
use Twig\TwigFunction;

class NavbarCreatedEventSubscriber implements EventSubscriberInterface
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
        $user = Sessions::isSessionValid() ? $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']["user"]) : false;

        $event->getNavbar()->addItems(
            new NavBarItemModel(
                id: "dashboard",
                name: Translation::fetch('CMSControl.Navbar.Item.Dashboard'),
                permissions: [],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/dashboard',
                icon: "fa-solid fa-gauge",
                target: HrefTargets::SELF,
            ),
            new NavbarDividerModel(
                title: Translation::fetch('CMSControl.Navbar.Divider.Editor'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_PAGES->value,
                    Permissions::WRITE_PAGES->value,
                    Permissions::READ_NAVBARS->value,
                    Permissions::WRITE_NAVBARS->value,
                    Permissions::READ_FILES->value,
                    Permissions::WRITE_FILES->value,
                ],
                permissionHaystack: $user->getPermissionArray()
            ),
            new NavBarItemModel(
                id: "pages",
                name: Translation::fetch('CMSControl.Navbar.Item.Pages'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_PAGES->value,
                    Permissions::WRITE_PAGES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/pages',
                icon: "fa-solid fa-file-lines",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "categories",
                name: Translation::fetch('CMSControl.Navbar.Item.Categories'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_CATEGORIES->value,
                    Permissions::WRITE_CATEGORIES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/categories',
                icon: "fa-solid fa-list",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "navbars",
                name: Translation::fetch('CMSControl.Navbar.Item.Navbar'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_NAVBARS->value,
                    Permissions::WRITE_NAVBARS->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/navbars',
                icon: "fa-solid fa-compass",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "files",
                name: Translation::fetch('CMSControl.Navbar.Item.Files'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_FILES->value,
                    Permissions::WRITE_FILES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/files',
                icon: "fa-solid fa-folder",
                target: HrefTargets::SELF,
            ),
            new NavbarDividerModel(
                title: Translation::fetch('CMSControl.Navbar.Divider.Administration'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_USERS->value,
                    Permissions::WRITE_USERS->value,
                    Permissions::READ_ROLES->value,
                    Permissions::WRITE_ROLES->value,
                    Permissions::READ_PLUGINS->value,
                    Permissions::WRITE_PLUGINS->value,
                    Permissions::READ_TEMPLATES->value,
                    Permissions::WRITE_TEMPLATES->value,
                    Permissions::READ_LAYOUTS->value,
                    Permissions::WRITE_LAYOUTS->value,
                ],
                permissionHaystack: $user->getPermissionArray()
            ),
            new NavBarItemModel(
                id: "users",
                name: Translation::fetch('CMSControl.Navbar.Item.Users'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_USERS->value,
                    Permissions::WRITE_USERS->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/users',
                icon: "fa-solid fa-users",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "layouts",
                name: Translation::fetch('CMSControl.Navbar.Item.Layouts'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_LAYOUTS->value,
                    Permissions::WRITE_LAYOUTS->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/layouts',
                icon: "fa-solid fa-paintbrush",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "templates",
                name: Translation::fetch('CMSControl.Navbar.Item.Template'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_TEMPLATES->value,
                    Permissions::WRITE_TEMPLATES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/templates',
                icon: "fa-solid fa-sitemap",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "roles",
                name: Translation::fetch('CMSControl.Navbar.Item.Roles'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_ROLES->value,
                    Permissions::WRITE_ROLES->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/roles',
                icon: "fa-solid fa-scroll",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "plugins",
                name: Translation::fetch('CMSControl.Navbar.Item.Plugins'),
                permissions: [
                    Permissions::SUPERUSER->value,
                    Permissions::READ_PLUGINS->value,
                    Permissions::WRITE_PLUGINS->value,
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/plugins',
                icon: "fa-solid fa-toolbox",
                target: HrefTargets::SELF,
            ),
            new NavBarItemModel(
                id: "settings",
                name: Translation::fetch('CMSControl.Navbar.Item.Settings'),
                permissions: [
                    Permissions::SUPERUSER->value
                ],
                permissionHaystack: $user->getPermissionArray(),
                url: 'admin/settings',
                icon: "fa-solid fa-cog",
                target: HrefTargets::SELF,
            ),
        );
    }
}
