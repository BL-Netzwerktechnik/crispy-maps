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
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\Models\CategoryModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;


class EditCategoriesPageController
{
    private UserController $userController;
    private CategoryDatabaseController $categoryDatabaseController;
    private LocationDatabaseController $locationDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_CATEGORIES->value,
    ];

    private array $deletePermissions = [
        Permissions::SUPERUSER->value
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
    }

    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->deletePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to delete categories', [], HTTP: 403);
            return;
        }

        
        $Category = $this->categoryDatabaseController->getCategoryById($id);

        if ($Category === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Category not found', [], HTTP: 404);
            return;
        }

        $this->categoryDatabaseController->beginTransaction();
        $this->locationDatabaseController->moveAllLocationsToNewCategory(
            $Category,
            $this->categoryDatabaseController->createFallbackCategory()
        );
        if (!$this->categoryDatabaseController->deleteCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete category', [], HTTP: 500);
            return;
        }
        $this->categoryDatabaseController->commitTransaction();
        http_response_code(204);
        return;
    }


    public function processPOSTRequest(int $id): void
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

        $Category = $this->categoryDatabaseController->getCategoryById($id);

        if ($Category === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Category not found', [], HTTP: 404);
            return;
        }

        $Category->setName($_POST['name']);
        $Category->setDescription($_POST['description']);
        $Category->setIcon(IconProviderController::fetchFromConfig($_POST['icon']));

        $this->categoryDatabaseController->beginTransaction();
        if(!$this->categoryDatabaseController->updateCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update category', [], HTTP: 500);
            return;
        }
        $this->categoryDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Category updated successfully', [], HTTP: 200);
    }

    public function preRender(int $id): void
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


        $Category = $this->categoryDatabaseController->getCategoryById($id);

        if ($Category === null) {
            ThemeVariables::set("ErrorMessage", "Kategorie nicht gefunden");
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("Category", $Category->toArray());

        echo Themes::render("maps/templates/Views/CmsControl/CategoryForm.twig");
    }
}
