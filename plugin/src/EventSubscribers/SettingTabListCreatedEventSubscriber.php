<?php

namespace blfilme\lostplaces\EventSubscribers;

use crisp\api\Config;
use crisp\api\Translation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Events\SettingsTabListCreatedEvent;
use Crispy\Helper;
use Crispy\Models\CmsControlNavbarModel;
use Crispy\Models\NavbarDividerModel;
use Crispy\Models\NavBarItemModel;
use Crispy\Models\SettingsNavItemModel;
use Crispy\Models\SettingsTabPaneModel;

class SettingTabListCreatedEventSubscriber implements EventSubscriberInterface
{
    private TemplateDatabaseController $templateDatabaseController;

    public function __construct()
    {
        $this->templateDatabaseController = new TemplateDatabaseController();
    }
    public static function getSubscribedEvents(): array
    {
        return [
            SettingsTabListCreatedEvent::class => 'onSettingsTabListCreated',
        ];
    }

    public function onSettingsTabListCreated(SettingsTabListCreatedEvent $event): void
    {
        

        $ConvertedList = [];

        foreach($this->templateDatabaseController->fetchAllTemplates() as $template) {
            $ConvertedList[] = [
                "value" => $template->getId(),
                "text" => sprintf("[%s] %s", $template->getSlug(), $template->getName()),
                "selected" => $template->getId() == Config::get("LostPlaces_LocationTemplate") ?? false,
            ];
        }

        ThemeVariables::set("LostPlaces_LocationTemplateList", $ConvertedList);

        $settingsTabListModel = $event->getTabList();
        $settingsTabListModel->addNavItem(new SettingsNavItemModel(
            text: "LostPlaces Karte",
            icon: "fas fa-map",
            tabPane: new SettingsTabPaneModel(
                content: Themes::render("maps/templates/Settings/TabContent.twig")
            )
        ));
    }
}
