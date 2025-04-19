<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\AccessTokens;
use Crispy\FileControllers\LayoutFileController;
use Exception;
use League\OAuth2\Client\Token\AccessToken;

class TokenModel
{


    private UserDatabaseController $userDatabaseController;

    public function __construct(
        private string $token,
        private AccessTokens $tokenType,
        private int|UserModel $user,
        private ?Carbon $expiresAt = null,
        private ?Carbon $createdAt = null,
        private ?int $id = null,
    ) {

        if (!$this->createdAt) {
            $this->createdAt = Carbon::now($_ENV['TZ']);
        }

        if (!$this->expiresAt) {
            $this->expiresAt = Carbon::now($_ENV['TZ'])->addHours(24);
        }

        $this->userDatabaseController = new UserDatabaseController();

        if (is_int($user)) {
            $this->user = $this->userDatabaseController->getUserById($user);
        }

        Logger::getLogger(__CLASS__)->debug('Created new TokenModel', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenType(): AccessTokens
    {
        return $this->tokenType;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): UserModel
    {
        return $this->user;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'user' => $this->user->toArray(),
            'type' => $this->tokenType->value,
            'expiresAt' => $this->expiresAt?->unix(),
            'createdAt' => $this->createdAt?->unix(),
        ];
    }

    public function setId(int $id): void
    {
        if (!is_null($this->id)) {
            throw new Exception('ID is already set');
        }
        $this->id = $id;
    }

    public function setExpiresAt(Carbon $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function setToken(AccessTokens $token): void
    {
        $this->token = $token;
    }

    public function setUser(UserModel $user): void
    {
        $this->user = $user;
    }

    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

}
