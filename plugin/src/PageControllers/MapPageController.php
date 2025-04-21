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


namespace blfilme\lostplaces\PageControllers;

use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\Translation;
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


class MapPageController
{
    private UserController $userController;

    public function __construct()
    {
        $this->userController = new UserController();
    }


    public function preRender(): void
    {
        if (!$this->userController->isSessionValid() && Config::get("LostPlaces_AuthRequired")) {
            header("Location: /admin/login");
            return;
        }

        echo Themes::render("lostplaces/templates/Views/Map.twig");
    }

    public function postRender(): void {}
}
