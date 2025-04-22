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


namespace blfilme\lostplaces\PageControllers\Public;

use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\Enums\LocationProperties;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use Crispy\Models\LayoutModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class LocationRenderPageController
{
    private UserController $userController;
    private TemplateDatabaseController $templateDatabaseController;
    private LocationDatabaseController $locationDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];

    public function __construct()
    {
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->userController = new UserController();
        $this->locationDatabaseController = new LocationDatabaseController();
    }

    public function preRender(int $id): void
    {

        if (Sessions::isSessionValid()) {
            ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));
        }
        if (!Config::exists("LostPlaces_LocationTemplate")) {
            ThemeVariables::set("ErrorMessage", "Die Vorlage für die Location-Seite ist nicht gesetzt. Bitte setze die Vorlage in den Plugin-Einstellungen.");
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        $Template = $this->templateDatabaseController->getTemplateById(Config::get("LostPlaces_LocationTemplate"));
        if ($Template === null) {
            ThemeVariables::set("ErrorMessage", "Die Vorlage für die Location-Seite existiert nicht. Bitte setze die Vorlage in den Plugin-Einstellungen.");
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }


        if (!$Location = $this->locationDatabaseController->getLocationById($id)) {
            header(("Location: /404"));
            return;
        }

        ThemeVariables::set("Location", $Location->toArray());
        ThemeVariables::set('AllLocationProperties', LocationProperties::cases());
        ThemeVariables::set("PropertyBadgeShowLabels", true);

        echo Themes::render($Template->getFrontendCodePath(), [core::THEME_BASE_DIR . "/build", "/plugins"]);
    }
}
