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
use Crispy\PageControllers\CmsControl\NavbarPageController;
use Crispy\PageControllers\CmsControl\NavbarsPageController;
use Crispy\PageControllers\CmsControl\PagePageController;
use Crispy\PageControllers\CmsControl\PagesPageController;
use Crispy\PageControllers\CmsControl\PluginsPageController;
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

class ThemeInitEventSubscriber implements EventSubscriberInterface
{
    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }


    public static function getSubscribedEvents(): array
    {
        return [
            ThemeEvents::SETUP => 'onSetup',
            ThemeEvents::SETUP_CLI => 'onSetupCli',
            ThemeEvents::PRE_RENDER => 'onPreRender'
        ];
    }

    public function onSetup(Event $event): void
    {
        Router::add(route: "/admin/login", routeType: RouteType::PUBLIC, class: LoginPageController::class, method: Route::GET);
        Router::add(route: "/admin/login", routeType: RouteType::PUBLIC, class: LoginPageController::class, method: Route::POST);

        Router::add(route: "/admin/register", routeType: RouteType::PUBLIC, class: RegisterPageController::class, method: Route::GET);
        Router::add(route: "/admin/register", routeType: RouteType::PUBLIC, class: RegisterPageController::class, method: Route::POST);


        Router::add(route: "/admin", routeType: RouteType::PUBLIC, class: DashboardPageController::class, method: Route::GET);
        Router::add(route: "/admin/dashboard", routeType: RouteType::PUBLIC, class: DashboardPageController::class, method: Route::GET);

        Router::add(route: "/admin/files", routeType: RouteType::PUBLIC, class: FilesPageController::class, method: Route::GET);
        Router::add(route: "/admin/files/connector", routeType: RouteType::PUBLIC, class: FileManagerConnectorPageController::class, method: Route::ANY);


        # Layouts
        Router::add(route: "/admin/layouts", routeType: RouteType::PUBLIC, class: LayoutsPageController::class, method: Route::GET);
        Router::add(route: "/admin/layouts/{id:\d+}", routeType: RouteType::PUBLIC, class: LayoutsPageController::class, method: Route::DELETE, callable: 'processDELETERequest');

        Router::add(route: "/admin/layouts/create", routeType: RouteType::PUBLIC, class: CreateLayoutPageController::class, method: Route::GET, callable: 'preRender');
        Router::add(route: "/admin/layouts/create", routeType: RouteType::PUBLIC, class: CreateLayoutPageController::class, method: Route::POST, callable: 'processPOSTRequest');

        Router::add(route: "/admin/layouts/{id:\d+}", routeType: RouteType::PUBLIC, class: EditLayoutPageController::class, method: Route::GET, callable: 'preRender');
        Router::add(route: "/admin/layouts/{id:\d+}", routeType: RouteType::PUBLIC, class: EditLayoutPageController::class, method: Route::PUT, callable: 'processPUTRequest');


        # Templates
        Router::add(route: "/admin/templates", routeType: RouteType::PUBLIC, class: TemplatesPageController::class, method: Route::GET);
        Router::add(route: "/admin/templates/{id:\d+}", routeType: RouteType::PUBLIC, class: TemplatesPageController::class, method: Route::DELETE, callable: 'processDELETERequest');

        Router::add(route: "/admin/templates/{id:\d+}", routeType: RouteType::PUBLIC, class: EditTemplatePageController::class, method: Route::PUT, callable: 'processPUTRequest');
        Router::add(route: "/admin/templates/{id:\d+}", routeType: RouteType::PUBLIC, class: EditTemplatePageController::class, method: Route::GET, callable: 'preRender');

        Router::add(route: "/admin/templates/create", routeType: RouteType::PUBLIC, class: CreateTemplatePageController::class, method: Route::GET, callable: 'preRender');
        Router::add(route: "/admin/templates/create", routeType: RouteType::PUBLIC, class: CreateTemplatePageController::class, method: Route::POST, callable: 'processPOSTRequest');




        # Categories
        Router::add(route: "/admin/categories", routeType: RouteType::PUBLIC, class: CategoriesPageController::class, method: Route::GET);
        Router::add(route: "/admin/categories", routeType: RouteType::PUBLIC, class: CategoriesPageController::class, method: Route::POST, callable: 'processPOSTRequest');
        Router::add(route: "/admin/categories.json", routeType: RouteType::PUBLIC, class: CategoriesPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/category/{id:\d+}", routeType: RouteType::PUBLIC, class: CategoryPageController::class, method: Route::DELETE, callable: 'processDELETERequest');
        Router::add(route: "/admin/category/{id:\d+}", routeType: RouteType::PUBLIC, class: CategoryPageController::class, method: Route::PUT, callable: 'processPUTRequest');

        Router::add(route: "/admin/category/{id:\d+}.json", routeType: RouteType::PUBLIC, class: CategoryPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/category/{id:\d+}/move", routeType: RouteType::PUBLIC, class: MoveCategoryPageController::class, method: Route::GET, callable: 'preRender');
        Router::add(route: "/admin/category/{id:\d+}/move", routeType: RouteType::PUBLIC, class: MoveCategoryPageController::class, method: Route::PUT, callable: 'processPUTRequest');



        # Pages
        Router::add(route: "/admin/pages", routeType: RouteType::PUBLIC, class: PagesPageController::class, method: Route::GET);
        Router::add(route: "/admin/pages", routeType: RouteType::PUBLIC, class: PagesPageController::class, method: Route::POST, callable: 'processPOSTRequest');
        Router::add(route: "/admin/pages.json", routeType: RouteType::PUBLIC, class: PagesPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/page/{id:\d+}.json", routeType: RouteType::PUBLIC, class: PagePageController::class, method: Route::GET, callable: 'json');
        Router::add(route: "/admin/page/{id:\d+}", routeType: RouteType::PUBLIC, class: PagePageController::class, method: Route::PUT, callable: 'processPUTRequest');
        Router::add(route: "/admin/page/{id:\d+}", routeType: RouteType::PUBLIC, class: PagePageController::class, method: Route::DELETE, callable: 'processDELETERequest');

        Router::add(route: "/admin/page/{id:\d+}/move", routeType: RouteType::PUBLIC, class: MovePagePageController::class, method: Route::PUT, callable: 'processPUTRequest');
        Router::add(route: "/admin/page/{id:\d+}/move", routeType: RouteType::PUBLIC, class: MovePagePageController::class, method: Route::GET, callable: 'preRender');




        # Roles
        Router::add(route: "/admin/roles", routeType: RouteType::PUBLIC, class: RolesPageController::class, method: Route::GET);
        Router::add(route: "/admin/roles", routeType: RouteType::PUBLIC, class: RolesPageController::class, method: Route::POST, callable: 'processPOSTRequest');
        Router::add(route: "/admin/roles.json", routeType: RouteType::PUBLIC, class: RolesPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/role/{id:\d+}", routeType: RouteType::PUBLIC, class: RolePageController::class, method: Route::DELETE, callable: 'processDELETERequest');
        Router::add(route: "/admin/role/{id:\d+}", routeType: RouteType::PUBLIC, class: RolePageController::class, method: Route::PUT, callable: 'processPUTRequest');

        Router::add(route: "/admin/role/{id:\d+}.json", routeType: RouteType::PUBLIC, class: RolePageController::class, method: Route::GET, callable: 'json');


        # Navbars
        Router::add(route: "/admin/navbars", routeType: RouteType::PUBLIC, class: NavbarsPageController::class, method: Route::GET);
        Router::add(route: "/admin/navbars", routeType: RouteType::PUBLIC, class: NavbarsPageController::class, method: Route::POST, callable: 'createNewUser');
        Router::add(route: "/admin/navbars.json", routeType: RouteType::PUBLIC, class: NavbarsPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/navbar/{id:\d+}.json", routeType: RouteType::PUBLIC, class: NavbarPageController::class, method: Route::GET, callable: 'json');

        # Users
        Router::add(route: "/admin/users", routeType: RouteType::PUBLIC, class: UsersPageController::class, method: Route::GET);
        Router::add(route: "/admin/users", routeType: RouteType::PUBLIC, class: UsersPageController::class, method: Route::POST, callable: 'createNewUser');
        Router::add(route: "/admin/users.json", routeType: RouteType::PUBLIC, class: UsersPageController::class, method: Route::GET, callable: 'json');

        Router::add(route: "/admin/user/{id:\d+}", routeType: RouteType::PUBLIC, class: UserPageController::class, method: Route::DELETE, callable: 'processDELETERequest');
        Router::add(route: "/admin/user/{id:\d+}", routeType: RouteType::PUBLIC, class: UserPageController::class, method: Route::PUT, callable: 'processPUTRequest');

        Router::add(route: "/admin/user/{id:\d+}.json", routeType: RouteType::PUBLIC, class: UserPageController::class, method: Route::GET, callable: 'json');


        
        Router::add(route: "/admin/plugins", routeType: RouteType::PUBLIC, class: PluginsPageController::class, method: Route::GET);

        Router::add(route: "/admin/settings", routeType: RouteType::PUBLIC, class: SettingsPageController::class, method: Route::GET);
        Router::add(route: "/admin/settings", routeType: RouteType::PUBLIC, class: SettingsPageController::class, method: Route::PUT, callable: 'processPUTRequest');
        Router::add(route: "/admin/settings/testEmail", routeType: RouteType::PUBLIC, class: SettingsPageController::class, method: Route::POST, callable: 'testEmailRequest');


        Router::add(route: "/admin/logout", routeType: RouteType::PUBLIC, class: LogoutPageController::class, method: Route::GET);

        # Catch All
        Router::add("/", RouteType::PUBLIC, RenderPageController::class);
        Router::add("{computedUrl:.*}?", RouteType::PUBLIC, RenderPageController::class);
    }

    public function onSetupCli(Event $event): void
    {
        CLI::get()->add(new CreateLayoutCommandController());
        CLI::get()->add(new GenerateFrontendCommandController());
    }

    public function onPreRender(Event $event): void
    {
        Themes::getRenderer()->addFunction(new TwigFunction('arrayToList', [new Helper(), 'arrayToList']));
        Themes::getRenderer()->addFunction(new TwigFunction('getUserById', [new UserDatabaseController(), 'getUserById']));


        $user = Sessions::isSessionValid() ? $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']["user"]) : false;



        if (Sessions::isSessionValid() && $user) {
            ThemeVariables::set("User", $user->toArray());
            
            EventController::getEventDispatcher()->dispatch(new NavbarCreatedEvent(CmsControlNavbarModel::getNavbar()));

            ThemeVariables::set('navbarItems', CmsControlNavbarModel::getNavbar()->getItems());
        }


        $sourceStrings = Translation::fetchAllByKey($_ENV['DEFAULT_LOCALE']);
        $localizedStrings = Translation::fetchAllByKey(ApiHelper::getLocale());

        $mergedStrings = array_merge($sourceStrings, $localizedStrings);

        ThemeVariables::set('AllTranslations', $mergedStrings);
    }
}
