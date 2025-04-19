<?php

namespace blfilme\lostplaces\EventSubscribers;

use crisp\api\Translation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use crisp\core\Themes;
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
            text: "LostPlaces Karte",
            icon: "fas fa-map",
            tabPane: new SettingsTabPaneModel(
                content: Themes::render("lostplaces/templates/Settings/TabContent.twig")
            )
        ));
    }
}
