<?php

namespace Crispy\Models;

use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\Enums\HrefTargets;

class SettingsTabPaneModel
{
    public function __construct(
        private string $content,
        private bool $hideButtons = false,
        private ?string $extraButtons = null,
        private ?string $id = null,
        
    ) {
        Logger::getLogger(__CLASS__)->debug('Created new SettingsNavItemModel', $this->toArray());

        if (empty($this->id)) {
            $this->id = Crypto::UUIDv4();
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExtraButtons(): ?string
    {
        return $this->extraButtons;
    }

    public function setExtraButtons(string $extraButtons): void
    {
        $this->extraButtons = $extraButtons;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'hideButtons' => $this->hideButtons,
            'extraButtons' => $this->extraButtons,
        ];
    }

    public function hideButtons(): bool
    {
        return $this->hideButtons;
    }

    public function showButtons(): bool
    {
        return !$this->hideButtons;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function areButtonsHidden(): bool
    {
        return $this->hideButtons;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
