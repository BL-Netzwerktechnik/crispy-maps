<?php

namespace blfilme\lostplaces\Models;

use Carbon\Carbon;
use Crispy\Models\UserModel;

class VoteModel
{
    public function __construct(
        private ?int $id,
        private LocationModel $location,
        private ?UserModel $user,
        private ?string $ipAddress,
        private bool $vote,
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null,
    ) {
        $this->createdAt = $this->createdAt ?? Carbon::now($_ENV['TZ'] ?? 'UTC');
        $this->updatedAt = $this->updatedAt ?? Carbon::now($_ENV['TZ'] ?? 'UTC');
    }

    /**
     * Get the ID of the category.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the location of the vote.
     *
     * @return LocationModel
     */
    public function getLocation(): LocationModel
    {
        return $this->location;
    }

    /**
     * Get the user who voted.
     *
     * @return UserModel|null
     */
    public function getUser(): ?UserModel
    {
        return $this->user;
    }

    /**
     * Get the IP address of the user who voted.
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Get the vote value.
     *
     * @return bool
     */
    public function getVote(): bool
    {
        return $this->vote;
    }

    /**
     * Convert the model to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location->toArray(),
            'author' => $this->user ? $this->user->toArray() : null,
            'ipAddress' => $this->ipAddress,
            'vote' => $this->vote,
            'createdAt' => $this->createdAt ? $this->createdAt->toDateTimeString() : null,
            'updatedAt' => $this->updatedAt ? $this->updatedAt->toDateTimeString() : null,
        ];
    }

    /**
     * Get the created at date of the category.
     *
     * @return Carbon|null
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the updated at date of the category.
     *
     * @return Carbon|null
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): self
    {
        $this->updatedAt = Carbon::now($_ENV['TZ'] ?? 'UTC');

        return $this;
    }
}
