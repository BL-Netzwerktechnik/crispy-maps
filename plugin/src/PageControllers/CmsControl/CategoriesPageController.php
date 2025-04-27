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


namespace blfilme\lostplaces\PageControllers\CmsControl;

use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;


class CategoriesPageController
{
    private UserController $userController;
    private CategoryDatabaseController $categoryDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_CATEGORIES->value,
    ];

    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_CATEGORIES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
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
        ThemeVariables::set("Categories", array_map(fn($category) => $category->toArray(), $this->categoryDatabaseController->fetchAllCategories()));



        echo Themes::render("lostplaces/templates/Views/CmsControl/Categories.twig");
    }

    public function postRender(): void {}
}
