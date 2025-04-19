<?php

namespace Crispy\Models;

class CmsControlNavbarModel
{
    private array $items = [];
    public function addItemsAfter(string $after, NavBarItemModel|NavbarDividerModel ...$items): CmsControlNavbarModel
{
    $newItems = [];
    $found = false;

    foreach ($this->getItems() as $existingItem) {
        $newItems[] = $existingItem;

        // Falls wir das Element mit der ID `$after` finden, fügen wir die neuen Items danach ein
        if ($existingItem instanceof NavBarItemModel && $existingItem->getId() === $after) {
            $newItems = array_merge($newItems, $items);
            $found = true;
        }
    }

    // Falls `$after` nicht gefunden wurde, hängen wir die neuen Items ans Ende der Liste
    if (!$found) {
        $newItems = array_merge($newItems, $items);
    }

    $this->items = $newItems;
    $GLOBALS['CMSControl_NAVBAR'] = $this;

    return $this;
}



    public function addItems(NavBarItemModel|NavbarDividerModel ...$items): CmsControlNavbarModel
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }

        $GLOBALS['CMSControl_NAVBAR'] = $this;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public static function getNavbar(): CmsControlNavbarModel
    {
        self::initNavbar();

        return $GLOBALS['CMSControl_NAVBAR'];
    }

    public static function initNavbar(): void
    {
        if (isset($GLOBALS['CMSControl_NAVBAR'])) {
            return;
        }

        $GLOBALS['CMSControl_NAVBAR'] = new CmsControlNavbarModel();
    }
}
