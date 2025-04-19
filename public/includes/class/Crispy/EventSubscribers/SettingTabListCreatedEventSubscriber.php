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
use Crispy\Events\SettingsTabListCreatedEvent;
use Crispy\Helper;
use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\NavbarDividerModel;
use Crispy\Models\NavBarItemModel;
use Crispy\Models\SettingsNavItemModel;
use Crispy\Models\SettingsTabPaneModel;

class SettingTabListCreatedEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            SettingsTabListCreatedEvent::class => 'onSettingsTabListCreated',
        ];
    }

    public function onSettingsTabListCreated(SettingsTabListCreatedEvent $event): void
    {
        
        $settingsTabListModel = $event->getTabList();
        $settingsTabListModel->addNavItem(new SettingsNavItemModel(
            text: Translation::fetch("CMSControl.Views.Settings.General.Title"),
            icon: "fas fa-cogs",
            active: true,
            tabPane: new SettingsTabPaneModel(
                content: Themes::render("Components/Settings/TabContent/General.twig")
            )
        ));

        
        $settingsTabListModel->addNavItem(new SettingsNavItemModel(
            text: Translation::fetch("CMSControl.Views.Settings.Authentication.Title"),
            icon: "fas fa-right-to-bracket",
            tabPane: new SettingsTabPaneModel(
                content: Themes::render("Components/Settings/TabContent/Authentication.twig")
            )
        ));

        
        $settingsTabListModel->addNavItem(new SettingsNavItemModel(
            text: Translation::fetch("CMSControl.Views.Settings.Email.Title"),
            icon: "fas fa-envelope",
            tabPane: new SettingsTabPaneModel(
                content: Themes::render("Components/Settings/TabContent/Email.twig"),
                extraButtons: Themes::render("Components/Settings/TestEmailButton.twig")
            )
        ));
    }
}
