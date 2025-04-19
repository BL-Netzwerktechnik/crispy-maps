<?php

namespace Crispy\Models;

class NavbarDividerModel
{
    /**
     * @param string $title
     * @param array  $permissions
     * @param array  $permissionHaystack
     */
    public function __construct(
        private string $title,
        private array $permissions = [],
        private array $permissionHaystack = []
    ) {
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return array
     */
    public function getPermissionHaystack(): array
    {
        return $this->permissionHaystack;
    }
}
