<?php

namespace blfilme\lostplaces\Models;

use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Interfaces\IconInterface;
use Carbon\Carbon;
use crisp\api\Config;
use crisp\core;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Helper;
use Crispy\Models\UserModel;

class LocationModel
{
    /**
     * LocationModel constructor.
     *
     * @param int|null $id
     * @param string $name 
     * @param string $description
     * @param LocationProperties[] $properties
     * @param LocationStatus $status
     * @param CoordinateModel $coordinates
     * @param UserModel $author
     * @param Carbon $createdAt
     * @param Carbon $updatedAt
     */
    public function __construct(
        public ?int $id,
        private string $name,
        private string $description,
        private array $properties,
        private ?string $youtube,
        private CategoryModel $category,
        private LocationStatus $status,
        private CoordinateModel $coordinates,
        private UserModel $author,
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null,
    ) {
        // Ensure properties are of type LocationProperties
        foreach ($this->properties as $property) {
            if (!$property instanceof LocationProperties) {
                throw new \InvalidArgumentException('Invalid property type');
            }
        }
        $this->createdAt = $this->createdAt ?? Carbon::now($_ENV['TZ'] ?? 'UTC');
        $this->updatedAt = $this->updatedAt ?? Carbon::now($_ENV['TZ'] ?? 'UTC');
    }

    public function getUploadFilePath(): string
    {
        return sprintf(
            "%s/lostplaces/%s",
            Config::get("LostPlaces_ProviderPath"),
            $this->getId()
        );
    }

    public function getUploadFilePathHash(): string
    {
        //ThemeVariables::set("elFinderUploadTargetHash", CrispyHelper::generateElFinderHash("testHash"));
        return Helper::generateElFinderHash($this->getUploadFilePath());
    }


    public function createFolderStructure(): void
    {
        $path = sprintf("%s/files/%s", core::PERSISTENT_DATA, $this->getUploadFilePath());
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    public function deleteFolderStructure(): void
    {
        $path = sprintf("%s/files/%s", core::PERSISTENT_DATA, $this->getUploadFilePath());
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * Get the name of the location
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Get the YouTube link of the location
     *
     * @return string|null
     */
    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    public function setYoutube(?string $youtube): self
    {
        $this->youtube = $youtube;
        return $this;
    }

    /**
     * Get the description of the location
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the coordinates of the location
     *
     * @return CoordinateModel
     */
    public function getCoordinates(): CoordinateModel
    {
        return $this->coordinates;
    }

    /**
     * Get the category of the location
     *
     * @return CategoryModel
     */
    public function getCategory(): CategoryModel
    {
        return $this->category;
    }

    /**
     * Get the icon of the location
     *
     * @return IconInterface
     */
    public function getIcon(): IconInterface
    {
        return $this->category->getIcon();
    }

    /**
     * Get the images of the location
     *
     * @todo Implement this method to return actual images
     * @return ImageModel[]
     */
    public function getImages(): array
    {
        return [];
    }

    /**
     * Get the image count of the location
     *
     * @todo Implement this method to return actual image count
     * @return int
     */
    public function getImageCount(): int
    {
        return 0;
    }

    /**
     * Get the properties of the location
     *
     * @return LocationProperties[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get the status of the location
     *
     * @return LocationStatus
     */
    public function getStatus(): LocationStatus
    {
        return $this->status;
    }

    /**
     * Get the author of the location
     *
     * @return UserModel
     */
    public function getAuthor(): UserModel
    {
        return $this->author;
    }

    /**
     * Get the created at timestamp of the location
     *
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * Get the updated at timestamp of the location
     *
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * Get the ID of the location
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function toGeoJSON(bool $editMarker = false): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    $this->coordinates->getLongitude(),
                    $this->coordinates->getLatitude(),
                ],
            ],
            'properties' => $this->toMarker($editMarker),
        ];
    }

    public function toMarker(bool $editMarker = false): array
    {
        ThemeVariables::set('location', $this->toArray());
        ThemeVariables::set('AllLocationProperties', LocationProperties::cases());

        if (!$editMarker) {

            $TemplateId = Config::get("LostPlaces_MapPopupTemplate");

            $templateDatabaseController = new TemplateDatabaseController();
            $template = $templateDatabaseController->getTemplateById((int)$TemplateId);
            if ($template !== null) {
                $popupContent = Themes::render($template->getFrontendCodePath(), [core::THEME_BASE_DIR . "/build", "/plugins"]);
            }
        }

        return [
            'id' => $this->id,
            'category' => $this->category->toArray(),
            'icon' => $this->getIcon()->toArray(),
            'popupContent' => $editMarker ? Themes::render("maps/templates/Components/CmsControl/MapPopup.twig") : ($popupContent ?? null),
            'markerColor' => $this->status->getColor()->value,
        ];
    }

    /**
     * Get the properties of the location as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'properties' => array_map(fn($property) => $property->value, $this->properties),
            'category' => $this->category->toArray(),
            'coordinates' => $this->coordinates->toArray(),
            'icon' => $this->getIcon()->toArray(),
            'status' => $this->status->value,
            'youtube' => $this->youtube,
            'author' => $this->author->toArray(),
            'markerColor' => $this->status->getColor()->value,
            'createdAt' => $this->createdAt->toDateTimeString(),
            'updatedAt' => $this->updatedAt->toDateTimeString(),
        ];
    }

    /**
     * Set the name of the location
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the description of the location
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the coordinates of the location
     *
     * @param CoordinateModel $coordinates
     * @return self
     */
    public function setCoordinates(CoordinateModel $coordinates): self
    {
        $this->coordinates = $coordinates;
        return $this;
    }

    /**
     * Set the category of the location
     *
     * @param CategoryModel $category
     * @return self
     */
    public function setCategory(CategoryModel $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Set the icon of the location
     *
     * @param IconInterface $icon
     * @return self
     */
    public function setIcon(IconInterface $icon): self
    {
        $this->category->setIcon($icon);
        return $this;
    }

    /**
     * Set the properties of the location
     *
     * @param LocationProperties[] $properties
     * @return self
     */
    public function setProperties(array $properties): self
    {
        foreach ($properties as $property) {
            if (!$property instanceof LocationProperties) {
                throw new \InvalidArgumentException('Invalid property type');
            }
        }
        $this->properties = $properties;
        return $this;
    }

    /**
     * Add a property to the location
     *
     * @param LocationProperties $property
     * @return self
     */
    public function addProperty(LocationProperties $property): self
    {
        if (!in_array($property, $this->properties)) {
            $this->properties[] = $property;
        }
        return $this;
    }

    /**
     * Remove a property from the location
     *
     * @param LocationProperties $property
     * @return self
     */
    public function removeProperty(LocationProperties $property): self
    {
        $this->properties = array_filter($this->properties, fn($p) => $p !== $property);
        return $this;
    }

    /**
     * Set the status of the location
     *
     * @param LocationStatus $status
     * @return self
     */
    public function setStatus(LocationStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the author of the location
     *
     * @param UserModel $author
     * @return self
     */
    public function setAuthor(UserModel $author): self
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Set the updated at timestamp of the location
     *
     * @return self
     */
    public function setUpdatedAt(): self
    {
        $this->updatedAt = Carbon::now($_ENV['TZ'] ?? 'UTC');
        return $this;
    }
}
