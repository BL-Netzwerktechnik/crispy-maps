<?php

namespace Crispy\Models;

use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\Enums\HrefTargets;

class SettingsNavItemModel
{
    public function __construct(
        private string $text,
        private string $icon,
        private SettingsTabPaneModel $tabPane,
        private bool $active = false,
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function isActive(): bool
    {
        return $this->active ?? false;
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

    public function getText(): string
    {
        return $this->text;
    }

    public function getTabPane(): SettingsTabPaneModel
    {
        return $this->tabPane;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'icon' => $this->icon,
            'active' => $this->active,
            'tabPane' => $this->tabPane->toArray(),
        ];
    }

    public function setTabPane(SettingsTabPaneModel $tabPane): void
    {
        $this->tabPane = $tabPane;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}
