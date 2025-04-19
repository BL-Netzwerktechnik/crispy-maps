<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Crispy\PageControllers\CmsControl\CategoriesPageController;
use Exception;

class CategoryModel
{

    private PageDatabaseController $pageDatabaseController;
    private CategoryDatabaseController $categoryDatabaseController;

    public function __construct(
        private string $name,
        private string $slug,
        private null|int|array|CategoryProperties $properties,
        private null|CategoryModel $parent = null,
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


        if (is_array($properties)) {
            $properties = array_reduce($properties, fn($carry, $tag) => $carry | $tag->value, 0);
        } elseif ($properties instanceof CategoryProperties) {
            $properties = $properties->value;
        }

        if(!$computedUrl){
            $this->computeUrl();
        }

        $this->pageDatabaseController = new PageDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();

        Logger::getLogger(__CLASS__)->debug('Created new CategoryModel', $this->toArray());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getProperties(): ?array
    {
        if (is_null($this->properties)) {
            return null;
        }
        $addedTags = [];
        foreach (CategoryProperties::cases() as $tag) {
            if ($this->getPropertiesAsInt() & $tag->value) {
                $addedTags[] = $tag;
            }
        }

        return $addedTags;
    }


    public function getPropertiesAsInt(): ?int
    {
        if (is_null($this->properties)) {
            return null;
        }
        if ($this->properties instanceof CategoryProperties) {
            return $this->properties->value;
        }

        if (is_array($this->properties)) {
            return array_reduce($this->properties, fn($carry, $tag) => $carry | $tag->value, 0);
        }

        return $this->properties;
    }



    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function getParent(): ?CategoryModel
    {
        return $this->parent;
    }

    public function canBeDeleted(): bool
    {
        return count($this->getChildren()) === 0;
    }

    public function getPages(): array
    {
        return $this->pageDatabaseController->fetchAllByCategory($this);
    }

    public function getChildren(): array
    {
        return $this->categoryDatabaseController->getChildrenByParentCategory($this);
    }

    public function hasProperty(CategoryProperties $property): bool
    {
        return ($this->properties & $property->value);
    }

    public function setProperties(int $properties): void
    {
        $this->properties = $properties;
    }

    public function addProperty(CategoryProperties $property): void
    {
        $this->properties |= $property->value;
    }

    public function removeProperty(CategoryProperties $property): void
    {
        $this->properties &= ~$property->value;
    }

    public function setParent(?CategoryModel $parent): void
    {
        $this->parent = $parent;
    }


    public function getComputedUrl(): string
    {
        return $this->computedUrl;
    }

    public function computeUrl(): void
    {
        $segments = [];

        // Traverse up through category parents recursively
        $category = $this->getParent();
        while ($category !== null) {
            array_unshift($segments, $category->getSlug()); // Add category slug to the beginning
            $category = $category->getParent(); // Move up the hierarchy
        }

        // Add the object's slug at the end
        $segments[] = $this->getSlug();

        // Convert array to string with "/"
        $this->computedUrl = implode("/", $segments);
    }

    public function updatePagesRecursive(): void
    {
        $children = $this->getChildren();
        foreach ($children as $child) {
            $child->updatePagesRecursive();
        }

        $pages = $this->getPages();
        foreach ($pages as $page) {
            $page->computeUrl();
            $this->pageDatabaseController->updatePage($page);
        }
    }

    public function isPrivate(): bool
    {
        if ($this->parent && $this->parent->isPrivate()) {
            return $this->parent->isPrivate();
        }
        return $this->hasProperty(CategoryProperties::VISIBILITY_PRIVATE);
    }

    public function isPrivateByParent(): bool
    {
        return !$this->hasProperty(CategoryProperties::VISIBILITY_PRIVATE);
    }

    protected function getTreeIcon(): string
    {
        if ($this->isPrivate()) {
            return $this->isPrivateByParent() ? 'fas fa-eye-low-vision' : 'fas fa-eye-slash';
        }
        return 'fas fa-folder';
    }

    public function toJsTreeItem(bool $withPages = false): jsTreeItemModel
    {
        $children = array_map(fn($category) => $category->toJsTreeItem($withPages), $this->getChildren());
        if($withPages){
            $pageChildren = array_map(fn($page) => $page->toJsTreeItem(), $this->getPages());
            $children = array_merge($children, $pageChildren);
        }

        return new jsTreeItemModel(
            id: "category-id-" . $this->id,
            text: $this->name,
            icon: $this->getTreeIcon(),
            state: new jsTreeStateModel(),
            children: $children,
            li_attr: [
                'category_id' => $this->id,
                'type' => 'category'
            ],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'properties' => $this->getPropertiesAsInt(),
            'slug' => $this->slug,
            'parent' => $this->parent?->toArray(),
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

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->computeUrl();
    }
}
