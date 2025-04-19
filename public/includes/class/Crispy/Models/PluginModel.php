<?php

namespace Crispy\Models;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Crypto;
use crisp\core\Logger;
use Crispy\Controllers\PluginController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PluginDatabaseController;
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class PluginModel
{

    private PluginDatabaseController $pluginDatabaseController;

    public function __construct(
        private string $name,
        private string $description,
        private string $version,
        private array $authors,
        private string $path,
        private bool $loaded = false
    ) {

        $this->pluginDatabaseController = new PluginDatabaseController();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getDirectory(): string
    {
        return PluginController::PLUGIN_DIR . '/' . $this->path;
    }

    public function loadPlugin(): void
    {
        if (file_exists(PluginController::PLUGIN_DIR . '/' . $this->path . '/Plugin.php')) {
            require_once PluginController::PLUGIN_DIR . '/' . $this->path . '/Plugin.php';

            $plugin = new \Plugin();
        } else {
            $this->pluginDatabaseController->beginTransaction();
            $this->pluginDatabaseController->deactivate($this->path);
            $this->pluginDatabaseController->commitTransaction();
        }
    }

    public function setLoaded(bool $loaded): void
    {
        $this->loaded = $loaded;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'authors' => $this->authors,
            'path' => $this->path,
            'loaded' => $this->loaded
        ];
    }
}
