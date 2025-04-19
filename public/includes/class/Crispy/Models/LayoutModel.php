<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\FileControllers\LayoutFileController;
use Exception;

class LayoutModel
{

    private LayoutFileController $layoutFileController;
    private LayoutDatabaseController $layoutDatabaseController;
    private TemplateDatabaseController $templateDatabaseController;

    public function __construct(
        private string $name,
        private ?string $content,
        private int $author,
        private string $slug,
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

        $this->layoutFileController = new LayoutFileController();
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateDatabaseController = new TemplateDatabaseController();

        Logger::getLogger(__CLASS__)->debug('Created new LayoutModel', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function getContent(): ?string
    {
        return $this->content;
    }

    public function canBeDeleted(): bool
    {
        return count($this->templateDatabaseController->fetchTemplatesByLayout($this)) === 0;
    }

    public function getAuthor(): int
    {
        return $this->author;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'author' => $this->author,
            'slug' => $this->slug,
            'createdAt' => $this->createdAt?->unix(),
            'updatedAt' => $this->updatedAt?->unix(),
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

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function setAuthor(int $author): void
    {
        $this->author = $author;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function generateFrontendCode(): void
    {
        $this->layoutFileController->saveLayout($this);
    }

    public function deleteFrontendCode(): void
    {
        $this->layoutFileController->deleteLayout($this);
    }


    public function getFrontendCodePath(): string
    {
        return LayoutFileController::LAYOUTS_DIRECTORY . '/' . $this->slug . '.twig';
    }
}
