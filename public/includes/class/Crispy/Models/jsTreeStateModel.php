<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class jsTreeStateModel
{
    public function __construct(
        private bool $opened = false,
        private bool $disabled = false,
        private bool $selected = false,
    ) {

        Logger::getLogger(__CLASS__)->debug('Created new jsTreeStateItem', $this->toArray());
    }

    public function getOpened(): ?int
    {
        return $this->opened;
    }

    public function getDisabled(): string
    {
        return $this->disabled;
    }

    public function getSelected(): string
    {
        return $this->selected;
    }

    public function toArray(): array
    {
        return [
            'opened' => $this->opened,
            'disabled' => $this->disabled,
            'selected' => $this->selected,
        ];
    }

    public function setOpened(bool $opened): void
    {
        $this->opened = $opened;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }
}
