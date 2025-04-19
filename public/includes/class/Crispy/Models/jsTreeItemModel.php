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

class jsTreeItemModel
{


    /**
     * @param string $id
     * @param string $text
     * @param string $icon
     * @param jsTreeStateModel $state
     * @param jsTreeItemModel[] $children
     * @param array|null $li_attr
     * @param array|null $a_attr
     */
    public function __construct(
        private string $id,
        private string $text,
        private string $icon,
        private jsTreeStateModel $state,
        private array $children = [],
        private ?array $li_attr = [],
        private ?array $a_attr = [],
    ) {

        Logger::getLogger(__CLASS__)->debug('Created new jsTreeItem', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getState(): jsTreeStateModel
    {
        return $this->state;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getLiAttr(): ?array
    {
        return $this->li_attr;
    }

    public function getAAttr(): ?array
    {
        return $this->a_attr;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'icon' => $this->icon,
            'state' => $this->state->toArray(),
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'li_attr' => $this->li_attr,
            'a_attr' => $this->a_attr,
        ];
    }

    public function addChildren(jsTreeItemModel $child): void
    {
        $this->children[] = $child;
    }
}
