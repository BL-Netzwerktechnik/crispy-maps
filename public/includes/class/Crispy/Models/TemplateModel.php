<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class TemplateModel
{

    private TemplateFileController $templateFileController;
    private LayoutDatabaseController $layoutDatabaseController;

    public function __construct(
        private string $name,
        private ?string $content,
        private int $author,
        private string $slug,
        private string $directory,
        private null|LayoutModel|int $layout,
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

        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateFileController = new TemplateFileController();

        if (is_int($layout)) {
            $this->layout = $this->layoutDatabaseController->getLayoutById($layout);
        }

        Logger::getLogger(__CLASS__)->debug('Created new TemplateModel', $this->toArray());
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

    public function getLayout(): ?LayoutModel
    {
        return $this->layout;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function canBeDeleted(): bool
    {
        return count((new PageDatabaseController())->fetchPagesByTemplate($this)) === 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'author' => $this->author,
            'directory' => $this->directory,
            'layout' => $this->layout?->toArray(),
            'slug' => $this->slug,
            'path' => $this->getPath(),
            'createdAt' => $this->createdAt?->unix(),
            'updatedAt' => $this->updatedAt?->unix(),
        ];
    }

    public function getPath(): string
    {
        if(!$this->directory || $this->directory == '') {
            return sprintf('%s.twig', $this->slug);
        }
        return sprintf('%s/%s.twig', $this->directory, $this->slug);
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
        $this->templateFileController->saveTemplate($this);
    }

    public function deleteFrontendCode(): void
    {
        $this->templateFileController->deleteTemplate($this);
    }

    public function getFrontendCodePath(): string
    {
        return TemplateFileController::TEMPLATES_DIRECTORY . '/' . $this->getPath();
    }


    public function setLayout(?LayoutModel $layout): void
    {
        $this->layout = $layout;
    }

    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }
}
