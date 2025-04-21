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

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\Models\CategoryModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;


class CreateCategoriesPageController
{
    private UserController $userController;
    private CategoryDatabaseController $categoryDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_CATEGORIES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
    }



    public function processPOSTRequest(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read or write categories', [], HTTP: 403);
            return;
        }


        if (empty($_POST['name'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "name"', [], HTTP: 400);
            return;
        }


        if (empty($_POST['description'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "description"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['icon'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "icon"', [], HTTP: 400);
            return;
        }

        $Category = new CategoryModel(
            id: null,
            name: $_POST['name'],
            description: $_POST['description'],
            icon: IconProviderController::fetchFromConfig($_POST['icon'])
        );
        $this->categoryDatabaseController->beginTransaction();
        if(!$this->categoryDatabaseController->insertCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create category', [], HTTP: 500);
            return;
        }
        $this->categoryDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Category created successfully', [], HTTP: 201);
    }

    public function preRender(): void
    {
        if (!$this->userController->isSessionValid()) {
            header("Location: /admin/login");
            return;
        }

        if (!$this->userController->checkPermissionStack($this->writePermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }



        echo Themes::render("lostplaces/templates/Views/CmsControl/CategoryForm.twig");
    }
}
