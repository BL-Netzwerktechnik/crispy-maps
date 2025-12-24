<?php

namespace blfilme\lostplaces\EventSubscribers;

use crisp\api\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Events\SettingsTabListCreatedEvent;
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

        $LostPlaces_LocationTemplateList = [];
        $LostPlaces_MapPopupTemplateList = [];

        foreach ($this->templateDatabaseController->fetchAllTemplates() as $template) {
            $LostPlaces_LocationTemplateList[] = [
                'value' => $template->getId(),
                'text' => sprintf('[%s] %s', $template->getSlug(), $template->getName()),
                'selected' => $template->getId() == Config::get('LostPlaces_LocationTemplate') ?? false,
            ];

            $LostPlaces_MapPopupTemplateList[] = [
                'value' => $template->getId(),
                'text' => sprintf('[%s] %s', $template->getSlug(), $template->getName()),
                'selected' => $template->getId() == Config::get('LostPlaces_MapPopupTemplate') ?? false,
            ];
        }

        ThemeVariables::set('LostPlaces_LocationTemplateList', $LostPlaces_LocationTemplateList);
        ThemeVariables::set('LostPlaces_MapPopupTemplateList', $LostPlaces_MapPopupTemplateList);

        $settingsTabListModel = $event->getTabList();
        $settingsTabListModel->addNavItem(new SettingsNavItemModel(
            text: 'Crispy Maps',
            icon: 'fas fa-map',
            tabPane: new SettingsTabPaneModel(
                content: Themes::render('maps/templates/Settings/TabContent.twig')
            )
        ));
    }
}
