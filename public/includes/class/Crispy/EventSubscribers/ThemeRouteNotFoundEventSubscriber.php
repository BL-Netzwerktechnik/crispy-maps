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
use crisp\Events\ThemePageErrorEvent;
use example\views\controllers\StartPageController;
use crisp\types\RouteType;
use Crispy\CommandControllers\CreateLayoutCommandController;
use Crispy\CommandControllers\GenerateFrontendCommandController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\HrefTargets;
use Crispy\Enums\Permissions;
use Crispy\Helper;
use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\NavbarDividerModel;
use Crispy\Models\NavBarItemModel;
use Crispy\PageControllers\CmsControl\CategoriesPageController;
use Crispy\PageControllers\CmsControl\CategoryPageController;
use Crispy\PageControllers\CmsControl\CreateLayoutPageController;
use Crispy\PageControllers\CmsControl\DashboardPageController;
use Crispy\PageControllers\CmsControl\EditLayoutPageController;
use Crispy\PageControllers\CmsControl\LayoutsPageController;
use Crispy\PageControllers\CmsControl\LoginPageController;
use Crispy\PageControllers\CmsControl\LogoutPageController;
use Crispy\PageControllers\CmsControl\MoveCategoryPageController;
use Crispy\PageControllers\CmsControl\PagePageController;
use Crispy\PageControllers\CmsControl\PagesPageController;
use Crispy\PageControllers\CmsControl\RegisterPageController;
use Crispy\PageControllers\RenderPageController;
use Crispy\PageControllers\RenderTranslationJsonPageController;
use Crispy\PageControllers\TestPageController;
use example\views\controllers\ApiPagecontroller;
use example\views\controllers\CronTestController;
use Phroute\Phroute\Route;
use Twig\TwigFunction;

class ThemeRouteNotFoundEventSubscriber implements EventSubscriberInterface
{
    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }


    public static function getSubscribedEvents(): array
    {
        return [
            ThemePageErrorEvent::ROUTE_NOT_FOUND => 'onRouteNotFound'
        ];
    }

    public function onRouteNotFound(Event $event): void
    {
        
        //$event->stopPropagation();
    }
}
