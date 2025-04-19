<?php

namespace Crispy\Models;

use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\NavbarDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\Enums\NavbarLinkTypes;
use Crispy\Enums\NavbarProperties;

class CrispyNavbarItemModel
{

    private CategoryDatabaseController $categoryDatabaseController;
    private PageDatabaseController $pageDatabaseController;
    private NavbarDatabaseController $navbarDatabaseController;

    public function __construct(
        private ?string $text,
        private PageModel|CategoryModel|string|null|int $target,
        private int|array|NavbarProperties $properties,
        private NavbarLinkTypes $type,
        private ?CrispyNavbarItemModel $parent = null,
        private int $order = 0,
        private bool $active = false,
        private ?string $icon = null,
        private ?int $id = null,
    ) {

        if (is_array($properties)) {
            $properties = array_reduce($properties, fn($carry, $tag) => $carry | $tag->value, 0);
        } elseif ($properties instanceof NavbarProperties) {
            $properties = $properties->value;
        }

        $this->pageDatabaseController = new PageDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->navbarDatabaseController = new NavbarDatabaseController();


        $this->target = match ($this->type) {
            NavbarLinkTypes::PAGE_TARGET => $this->pageDatabaseController->getPageById($target),
            NavbarLinkTypes::CATEGORY_TARGET => $this->categoryDatabaseController->getCategoryById($target),
            default => $target
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTarget(): PageModel|CategoryModel|string|null
    {
        return $this->target;
    }

    public function getProperties(): NavbarProperties
    {
        return $this->properties;
    }

    public function getType(): NavbarLinkTypes
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->active ?? false;
    }

    public function getParent(): ?CrispyNavbarItemModel
    {
        return $this->parent;
    }

    public function isTargetPage(): bool
    {
        return $this->type === NavbarLinkTypes::PAGE_TARGET;
    }

    public function isTargetCategory(): bool
    {
        return $this->type === NavbarLinkTypes::CATEGORY_TARGET;
    }

    public function isTargetUrl(): bool
    {
        return $this->type === NavbarLinkTypes::URL_TARGET;
    }

    public function getChildren(): array
    {
        return $this->navbarDatabaseController->getChildrenByParentNavbar($this);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'properties' => $this->properties->value,
            'type' => $this->type->value,
            'url' => $this->getUrl(),
            'target' => $this->isTargetUrl() ? $this->target : $this->target?->toArray(),
            'children' => array_map(fn($child) => $child->toArray(), $this->getChildren()),
            'active' => $this->active,
            'icon' => $this->icon,
        ];
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function setActive(): void
    {
        $this->active = true;
    }

    public function setInactive(): void
    {
        $this->active = false;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function hasChildren(): bool
    {
        return !empty($this->getChildren());
    }

    public function setTarget(PageModel|CategoryModel|string|null $target): void
    {
        $this->target = $target;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setType(NavbarLinkTypes $type): void
    {
        $this->type = $type;
    }


    public function getPropertiesAsInt(): ?int
    {
        if (is_null($this->properties)) {
            return null;
        }
        if ($this->properties instanceof NavbarProperties) {
            return $this->properties->value;
        }

        if (is_array($this->properties)) {
            return array_reduce($this->properties, fn($carry, $tag) => $carry | $tag->value, 0);
        }

        return $this->properties;
    }


    public function hasProperty(NavbarProperties $property): bool
    {
        return ($this->properties & $property->value);
    }

    public function setProperties(int $properties): void
    {
        $this->properties = $properties;
    }

    public function addProperty(NavbarProperties $property): void
    {
        $this->properties |= $property->value;
    }

    public function removeProperty(NavbarProperties $property): void
    {
        $this->properties &= ~$property->value;
    }

    public function isPrivate(): bool
    {
        if ($this->parent && $this->parent->isPrivate()) {
            return $this->parent->isPrivate();
        }
        return $this->hasProperty(NavbarProperties::VISIBILITY_PRIVATE);
    }

    public function isPrivateByParent(): bool
    {
        return !$this->hasProperty(NavbarProperties::VISIBILITY_PRIVATE);
    }

    protected function getTreeIcon(): string
    {
        if ($this->isPrivate()) {
            return $this->isPrivateByParent() ? 'fas fa-eye-low-vision' : 'fas fa-eye-slash';
        }

        if ($this->hasChildren()) {
            return 'fas fa-square-caret-down';
        }

        if ($this->isTargetPage()) {
            return 'fas fa-file';
        }

        if ($this->isTargetCategory()) {
            return 'fas fa-folder';
        }

        return 'fas fa-link';
    }

    public function getUrl(): string
    {
        return match ($this->type) {
            NavbarLinkTypes::PAGE_TARGET, NavbarLinkTypes::CATEGORY_TARGET => $this->target->getComputedUrl(),
            NavbarLinkTypes::URL_TARGET => $this->target,
        };
    }

    public function toJsTreeItem(): jsTreeItemModel
    {
        $children = array_map(fn($navbar) => $navbar->toJsTreeItem(), $this->getChildren());
        return new jsTreeItemModel(
            id: "navbar-id-" . $this->id,
            text: $this->text,
            icon: $this->getTreeIcon(),
            state: new jsTreeStateModel(),
            children: $children,
            li_attr: [
                'navbar_id' => $this->id,
                'type' => 'navbar'
            ],
        );
    }
}
