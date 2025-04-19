<?php

namespace Crispy\Models;

use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\Enums\HrefTargets;

class NavBarItemModel
{
    public function __construct(
        private string $name,
        private string $url,
        private array $children = [],
        private HrefTargets $target = HrefTargets::SELF,
        private ?bool $active = null,
        private array $permissions = [],
        private array $permissionHaystack = [],
        private ?string $icon = null,
        private ?string $id = null,
    ) {
        Logger::getLogger(__CLASS__)->debug('Created new NavBarItemModel', $this->toArray());

        if (empty($this->id)) {
            $this->id = Crypto::UUIDv4();
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function isActive(): bool
    {
        return $this->active ?? false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermissionHaystack(): array
    {
        return $this->permissionHaystack;
    }

    public function isNullState(): bool
    {
        return is_null($this->active);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getTarget(): HrefTargets
    {
        return $this->target;
    }

    public function addChild(NavBarItemModel $child): void
    {
        $this->children[] = $child;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'children' => array_map(fn ($child) => $child->toArray(), $this->children),
            'target' => $this->target,
            'active' => $this->active,
            'icon' => $this->icon,
        ];
    }

    public function setTarget(HrefTargets $target): void
    {
        $this->target = $target;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
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
        return !empty($this->children);
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function setPermissionHaystack(array $permissionHaystack): void
    {
        $this->permissionHaystack = $permissionHaystack;
    }

    public function removeChild(NavBarItemModel $child): void
    {
        $this->children = array_filter($this->children, fn ($c) => $c->getId() !== $child->getId());
    }
}
