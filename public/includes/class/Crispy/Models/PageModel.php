<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Enums\PageProperties;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Crispy\QuillParsers\TableListener;
use Exception;
use nadar\quill\Lexer;

class PageModel
{
    private TemplateDatabaseController $templateDatabaseController;
    private CategoryDatabaseController  $categoryDatabaseController;

    public function __construct(
        private string $name,
        private ?string $content,
        private int $author,
        private string $slug,
        private null|PageProperties|int $properties,
        private null|CategoryModel|int $category = null,
        private TemplateModel|int $template,
        private ?string $computedUrl = null,
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

        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();

        if (is_int($template)) {
            $this->template = $this->templateDatabaseController->getTemplateById($template);
        }

        if (is_int($category)) {
            $this->category = $this->categoryDatabaseController->getCategoryById($category);
        }

        if ($properties instanceof PageProperties) {
            $this->properties = $properties->value;
        }

        if(!$computedUrl){
            $this->computeUrl();
        }

        Logger::getLogger(__CLASS__)->debug('Created new PageModel', $this->toArray());
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

    public function getTemplate(): ?TemplateModel
    {
        return $this->template;
    }

    public function getProperties(): int
    {
        return $this->properties;
    }

    public function getCategory(): ?CategoryModel
    {
        return $this->category;
    }

    public function hasProperty(PageProperties $property): bool
    {
        return ($this->properties & $property->value);
    }

    public function setTemplate(TemplateModel $template): void
    {
        $this->template = $template;
    }

    public function setProperties(int $properties): void
    {
        $this->properties = $properties;
    }

    public function addProperty(PageProperties $property): void
    {
        $this->properties |= $property->value;
    }

    public function setCategory(?CategoryModel $category): void
    {
        $this->category = $category;
    }

    public function removeProperty(PageProperties $property): void
    {
        $this->properties &= ~$property->value;
    }

    public function getComputedUrl(): string
    {
        return $this->computedUrl;
    }

    
    public function isPrivate(): bool
    {
        if ($this->category && $this->category->isPrivate()) {
            return $this->category->isPrivate();
        }
        return $this->hasProperty(PageProperties::VISIBILITY_PRIVATE);
    }

    public function isPrivateByParent(): bool
    {
        return !$this->hasProperty(PageProperties::VISIBILITY_PRIVATE);
    }

    protected function getTreeIcon(): string
    {
        if ($this->isPrivate()) {
            return $this->isPrivateByParent() ? 'fas fa-file-circle-exclamation' : 'fas fa-file-circle-xmark';
        }
        if($this->hasProperty(PageProperties::OPTION_FRONTPAGE)){
            return 'fas fa-flag-checkered';
        }
        if($this->hasProperty(PageProperties::OPTION_NOT_FOUND)){
            return 'fas fa-ghost';
        }
        return 'fas fa-file-lines';
    }

    public function toJsTreeItem(): jsTreeItemModel
    {
        return new jsTreeItemModel(
            id: "page-id-" . $this->id,
            text: $this->name,
            icon: $this->getTreeIcon(),
            state: new jsTreeStateModel(),
            children: [],
            li_attr: [
                'page_id' => $this->id,
                'type' => 'page'
            ],
        );
    }

    public function computeUrl(): void
    {
        $segments = [];

        // Traverse up through category parents recursively
        $category = $this->getCategory();
        while ($category !== null) {
            array_unshift($segments, $category->getSlug()); // Add category slug to the beginning
            $category = $category->getParent(); // Move up the hierarchy
        }

        // Add the object's slug at the end
        $segments[] = $this->getSlug();

        // Convert array to string with "/"
        $this->computedUrl = implode("/", $segments);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => $this->content,
            'author' => $this->author,
            'isPrivate' => $this->isPrivate(),
            'properties' => $this->getProperties(),
            'template' => $this->template?->toArray(),
            'slug' => $this->slug,
            'computedUrl' => $this->computedUrl,
            'category' => $this->category?->toArray(),
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
        $this->computeUrl();
        $this->slug = $slug;
    }
}
