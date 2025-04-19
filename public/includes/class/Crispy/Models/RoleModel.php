<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class RoleModel
{

    private RoleDatabaseController $roleDatabaseController;
    private UserDatabaseController $userDatabaseController;

    public function __construct(
        private string $name,
        private ?int $permissions = null,
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null,
        private ?int $id = null,
    ) {

        if (!$this->createdAt) {
            $this->createdAt = Carbon::now($_ENV['TZ']);
        }

        if (!$this->updatedAt) {
            $this->updatedAt = Carbon::now($_ENV['TZ']);
        }

        $this->roleDatabaseController = new RoleDatabaseController();
        $this->userDatabaseController = new UserDatabaseController();

        Logger::getLogger(__CLASS__)->debug('Created new RoleModel', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermissions(): ?int
    {
        return $this->permissions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTranslatedName(): string
    {
        return Translation::fetch($this->name);
    }


    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'createdAt' => $this->createdAt?->unix(),
            'updatedAt' => $this->updatedAt?->unix(),
            'badge' => $this->generateBadge()
        ];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setId(int $id): void
    {
        if (!is_null($this->id)) {
            throw new Exception('ID is already set');
        }
        $this->id = $id;
    }

    public function setPermissions(int $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function addPermission(int $permissions): void
    {
        $this->permissions |= $permissions;
    }

    public function removePermission(int $permissions): void
    {
        $this->permissions &= ~$permissions;
    }


    public function hasPermission(int $permissions): bool
    {
        return ($this->permissions & $permissions) === $permissions;
    }

    public function canBeDeleted(): bool
    {
        return count($this->userDatabaseController->fetchAllUsersByRoleId($this->id)) === 0;
    }
    
    protected function getTreeIcon(): string
    {
        if(!$this->hasPermission(Permissions::LOGIN->value)) {
            return 'fas fa-lock';
        }
        if($this->hasPermission(Permissions::SUPERUSER->value)) {
            return 'fas fa-building-shield';
        }

        return 'fas fa-id-badge';
    }
    
    public function toJsTreeItem(bool $withUsers = false): jsTreeItemModel
    {
        return new jsTreeItemModel(
            id: "role-id-" . $this->id,
            text: Translation::fetch($this->name),
            icon: $this->getTreeIcon(),
            state: new jsTreeStateModel(
                opened: true,
                disabled: $withUsers
            ),
            children: $withUsers ? array_map(fn($user) => $user->toJsTreeItem(), $this->userDatabaseController->fetchAllUsersByRoleId($this->id)) : [],
            li_attr: [
                'role_id' => $this->id,
            ],
        );
    }

    public function update(): void
    {
        if ($this->id) {
            $this->roleDatabaseController->updateRole($this);
        } else {
            throw new Exception('Cannot update role without ID');
        }
    }

    public function generateBadge(): string
    {
        return sprintf('<span class="badge bg-primary">%s</span>', Translation::fetch($this->name));
    }
}
