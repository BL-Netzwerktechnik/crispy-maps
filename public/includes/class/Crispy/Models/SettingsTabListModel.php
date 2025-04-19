<?php

namespace Crispy\Models;

class SettingsTabListModel
{
    private array $items = [];

    public function addNavItem(SettingsNavItemModel ...$items): SettingsTabListModel
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }

        $GLOBALS['CMSControl_SettingsTabList'] = $this;

        return $this;
    }

    /**
     * Undocumented function
     *
     * @return SettingsNavItemModel[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public static function getTabList(): SettingsTabListModel
    {
        self::initTabList();

        return $GLOBALS['CMSControl_SettingsTabList'];
    }

    public static function initTabList(): void
    {
        if (isset($GLOBALS['CMSControl_SettingsTabList'])) {
            return;
        }

        $GLOBALS['CMSControl_SettingsTabList'] = new SettingsTabListModel();
    }
}
