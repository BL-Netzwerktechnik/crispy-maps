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

use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
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
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class MoveCategoryPageController
{
    private CategoryDatabaseController $categoryDatabaseController;
    private UserController $userController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_CATEGORIES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
    }


    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write categories', [], HTTP: 403);
            return;
        }



        if (!$Category = $this->categoryDatabaseController->getCategoryById($id)) {
            http_response_code(404);
            return;
        }

        $Body = RESTfulAPI::getBody();

        $isError = false;
        $fieldErrors = [];

        if (is_string($Body) || empty($Body) || is_null($Body)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid JSON in body', []);
            exit;
        }


        if (!$Category = $this->categoryDatabaseController->getCategoryById($id)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Category not found', [], HTTP: 404);
            return;
        }

        $this->categoryDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('parent')) {
            if ($Body['parent'] === null) {
                $Category->setParent(null);
            } elseif (!is_numeric($Body['parent']) || $Body['parent'] === $Category->getId()) {
                $isError = true;
                $fieldErrors[] = 'Invalid JSON "parent"';
            } elseif ($Parent = $this->categoryDatabaseController->getCategoryById($Body['parent'])) {
                $Category->setParent($Parent);
            } else {
                $isError = true;
                $fieldErrors[] = 'Invalid Category "parent"';
            }
        }




        if ($isError) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        $Category->setSlug(CrispyHelper::slugify($Category->getName()) . '-' . $Category->getId());

        if (!$this->categoryDatabaseController->updateCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to move category', [], HTTP: 500);

            return;
        }
        $this->categoryDatabaseController->commitTransaction();


        $this->categoryDatabaseController->beginTransaction();

        $Category->updatePagesRecursive();

        $this->categoryDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Category moved', [
            'category' => $Category->toArray(),
        ], HTTP: 200);
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

        if (!$Category = $this->categoryDatabaseController->getCategoryById($id)) {
            http_response_code(404);
            return;
        }

        ThemeVariables::set("Category", $Category->toArray());


        echo Themes::render("Views/MoveCategory.twig");
    }
}
