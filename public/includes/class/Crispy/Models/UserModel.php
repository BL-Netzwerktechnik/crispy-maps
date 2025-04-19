<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class UserModel
{

    private UserDatabaseController $userDatabaseController;

    public function __construct(
        private string $username,
        private string $name,
        private string $email,
        private bool $emailVerified,
        private ?RoleModel $role = null,
        private ?Carbon $lastLogin = null,
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null,
        private ?string $password = null,
        private ?int $id = null,
    ) {

        if (!$this->createdAt) {
            $this->createdAt = Carbon::now($_ENV['TZ']);
        }

        if (!$this->updatedAt) {
            $this->updatedAt = Carbon::now($_ENV['TZ']);
        }


        if (!$password) {
            $this->setPassword(Crypto::UUIDv4());
        }

        $this->userDatabaseController = new UserDatabaseController();

        Logger::getLogger(__CLASS__)->debug('Created new UserModel', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getLastLogin(): ?Carbon
    {
        return $this->lastLogin;
    }

    public function getRole(): ?RoleModel
    {
        return $this->role;
    }

    
    
    protected function getTreeIcon(): string
    {
        if(!$this->hasPermission(Permissions::LOGIN->value)) {
            return 'fas fa-user-lock';
        }
        if($this->hasPermission(Permissions::SUPERUSER->value)) {
            return 'fas fa-user-gear';
        }

        return 'fas fa-user';
    }
    
    public function toJsTreeItem(): jsTreeItemModel
    {
        return new jsTreeItemModel(
            id: "user-id-" . $this->id,
            text: Translation::fetch($this->name),
            icon: $this->getTreeIcon(),
            state: new jsTreeStateModel(
                opened: true,
            ),
            children: [],
            li_attr: [
                'user_id' => $this->id,
            ],
        );
    }

    public function setRole(RoleModel $role): void
    {
        $this->role = $role;
    }

    public function getPermissions(): ?int
    {
        return $this?->role->getPermissions();
    }

    public function getPermissionArray(): array
    {

        $permissions = [];

        foreach (Permissions::cases() as $permission) {
            if ($this->hasPermission($permission->value)) {
                $permissions[] = $permission->value;
            }
        }

        return $permissions;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->toArray(),
            'emailVerified' => $this->emailVerified,
            'lastLogin' => $this->lastLogin?->unix(),
            'createdAt' => $this->createdAt?->unix(),
            'updatedAt' => $this->updatedAt?->unix()
        ];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setId(int $id): void
    {
        if (!is_null($this->id)) {
            throw new Exception('ID is already set');
        }
        $this->id = $id;
    }

    public function hasPermission(int $permissions): bool
    {
        return $this->role->hasPermission($permissions);
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash(password: $password, algo: PASSWORD_BCRYPT, options: ['cost' => 12]);
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setEmailVerified(bool $emailVerified): void
    {
        $this->emailVerified = $emailVerified;
    }

    public function updateLastLogin(): void
    {
        $this->lastLogin = Carbon::now($_ENV['TZ']);
    }

    public function update(): void
    {
        if ($this->id) {
            $this->userDatabaseController->updateUser($this);
        } else {
            throw new Exception('Cannot update user without ID');
        }
    }

    public static function fetchSystemUser(): UserModel
    {
        return new UserModel(
            username: 'system',
            name: 'System',
            email: 'system@crispcms.invalid',
            emailVerified: true,
            password: Crypto::UUIDv4(),
            id: 0
        );
    }

}
