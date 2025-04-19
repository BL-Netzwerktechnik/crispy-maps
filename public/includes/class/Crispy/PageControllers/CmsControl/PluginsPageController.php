<?php
/*
 * Copyright (c) 2022. Pixelcowboys Werbeagentur, All Rights Reserved
 *
 *  @author Justin René Back <jb@pixelcowboys.de>
 *  @link https://vcs.pixelcowboys.de/crispcms/core/
 *
 *  Unauthorized copying of this file, via any medium is strictly prohibited
 *  Proprietary and confidential
 *
 */


namespace Crispy\PageControllers\CmsControl;

use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\Translation;
use crisp\Controllers\EventController;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\PluginController;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\PluginDatabaseController;
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Events\PluginActivatedEvent;
use Crispy\Events\PluginDeactivatedEvent;
use Crispy\Events\SettingsTabListCreatedEvent;
use Crispy\EventSubscribers\SettingTabListCreatedEventSubscriber;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use Crispy\Models\RoleModel;
use Crispy\Models\SettingsTabListModel;
use JetBrains\PhpStorm\ArrayShape;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;


class PluginsPageController
{
    private UserController $userController;
    private PluginController $pluginController;
    private PluginDatabaseController $pluginDatabaseController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_PLUGINS->value,
        Permissions::WRITE_PLUGINS->value
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PLUGINS->value
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->pluginController = new PluginController();
        $this->pluginDatabaseController = new PluginDatabaseController();
    }

    public function preRender(): void
    {
        if (!$this->userController->isSessionValid()) {
            header("Location: /admin/login");
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));


        if (isset($_GET["activate"]) && isset($_GET["name"])) {
            if ($this->pluginDatabaseController->getPluginByPath($_GET["name"])) {
                ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.PluginAlreadyActive'));
                echo Themes::render("Views/ErrorPage.twig");
                return;
            }
            if (!$Plugin = $this->pluginController->getPlugin($_GET["name"])) {
                ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.PluginNotFound'));
                echo Themes::render("Views/ErrorPage.twig");
                return;
            }
            $this->pluginDatabaseController->beginTransaction();
            $this->pluginDatabaseController->activate($_GET["name"]);
            try {
                $Plugin->loadPlugin();
                EventController::getEventDispatcher()->dispatch(new PluginActivatedEvent($Plugin));
            } catch (\Exception $e) {
                $this->pluginDatabaseController->rollbackTransaction();
                ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.PluginError'));
                echo Themes::render("Views/ErrorPage.twig");
                return;
            }
            if($this->pluginDatabaseController->inTransaction()){
                $this->pluginDatabaseController->commitTransaction();
            }
            header("Location: /admin/plugins");
            return;
        } elseif (isset($_GET["deactivate"]) && isset($_GET["name"])) {
            if (!$Plugin = $this->pluginController->getPlugin($_GET["name"])) {
                ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.PluginNotFound'));
                echo Themes::render("Views/ErrorPage.twig");
                return;
            }
            $this->pluginDatabaseController->beginTransaction();
            $this->pluginDatabaseController->deactivate($_GET["name"]);
            $this->pluginDatabaseController->commitTransaction();
            EventController::getEventDispatcher()->dispatch(new PluginDeactivatedEvent($Plugin));
            header("Location: /admin/plugins");
            return;
        } elseif (isset($_GET["delete"]) && isset($_GET["name"])) {
            if ($this->pluginController->canLoadPlugin($_GET["name"])) {
                $Plugin = $this->pluginController->getPlugin($_GET["name"]);

                if ($Plugin) {
                    $this->pluginController->deleteDir($Plugin->getDirectory());
                }
            }
            header("Location: /admin/plugins");
            return;
        }

        ThemeVariables::set('Plugins', array_map(function ($plugin) {

            if ($this->pluginDatabaseController->getPluginByPath($plugin->getPath())) {
                $plugin->setLoaded(true);
            }


            return $plugin->toArray();
        }, $this->pluginController->listAllPlugins()));

        echo Themes::render("Views/Plugins.twig");
    }


    public function postRender(): void {}
}
