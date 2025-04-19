<?php

namespace Crispy\Controllers;

use Carbon\Carbon;
use crisp\core;
use crisp\core\Logger;
use Crispy\Models\LayoutModel;
use Crispy\Models\PluginModel;
use Crispy\Models\TemplateModel;
use Crispy\Models\UserModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use InvalidArgumentException;
use PDO;

class PluginController
{
    public const PLUGIN_DIR = '/plugins';



    public function __construct() {}

    public function canLoadPlugin(string $path): bool
    {

        if (!file_exists(self::PLUGIN_DIR . '/' . $path)) {
            return false;
        }
        if (!file_exists(self::PLUGIN_DIR . '/' . $path . '/Plugin.php')) {
            return false;
        }
        if (!file_exists(self::PLUGIN_DIR . '/' . $path . '/composer.json')) {
            return false;
        }

        return true;
    }

    public function deleteDir(string $dirPath): void
    {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * Get Plugin data from filesystem composer.json
     *
     * @param string $pluginName
     * @return PluginModel
     */
    public function getPlugin(string $path): PluginModel
    {
        if (!file_exists(self::PLUGIN_DIR . '/' . $path)) {
            throw new Exception('Plugin Dir not found');
        }
        if (!file_exists(self::PLUGIN_DIR . '/' . $path . '/Plugin.php')) {
            throw new Exception('Plugin.php not found');
        }
        if (!file_exists(self::PLUGIN_DIR . '/' . $path . '/composer.json')) {
            throw new Exception('composer.json not found');
        }
        $pluginData = json_decode(file_get_contents(self::PLUGIN_DIR . '/' . $path . '/composer.json'), true);

        return new PluginModel(
            name: $pluginData['name'] ?? "@unknown",
            description: $pluginData['description'] ?? "",
            version: $pluginData['version'] ?? "0.0.0",
            authors: $pluginData['authors'] ?? [
                "name" => "Unknown",
                "email" => "Unknown",
                "homepage" => "Unknown",
                "role" => "Unknown",
            ],
            path: $path
        );
    }

    public function listAllPlugins(): array
    {
        $plugins = [];
        $pluginDir = scandir(self::PLUGIN_DIR);

        foreach ($pluginDir as $plugin) {
            if (is_dir(self::PLUGIN_DIR . '/' . $plugin) && file_exists(self::PLUGIN_DIR . '/' . $plugin . '/Plugin.php') && file_exists(self::PLUGIN_DIR . '/' . $plugin . '/composer.json')) {
                $plugins[] = $this->getPlugin($plugin);
            }
        }

        return $plugins;
    }
}
