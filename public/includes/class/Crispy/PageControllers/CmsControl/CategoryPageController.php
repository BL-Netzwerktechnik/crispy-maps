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


class CategoryPageController
{
    private CategoryDatabaseController $categoryDatabaseController;
    private PageDatabaseController $pageDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_CATEGORIES->value,
        Permissions::WRITE_CATEGORIES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_CATEGORIES->value,
    ];



    public function __construct()
    {
        $this->pageDatabaseController = new PageDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->userController = new UserController();
    }

    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read or write categories', [], HTTP: 403);
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

        if (RESTfulAPI::BodyParameterExists('name') && !is_string($Body['name'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "name"';
        } elseif (RESTfulAPI::BodyParameterExists('name')) {
            $Category->setName($Body['name']);
        }


        if (RESTfulAPI::BodyParameterExists('slug') && !is_string($Body['slug'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "slug"';
        } elseif (RESTfulAPI::BodyParameterExists('slug') && !empty($Body['slug'])) {
            
            $Category->setSlug($Body['slug']);
        } else {
            if ($this->categoryDatabaseController->getCategoryBySlug($Category->getSlug())) {
                $Category->setSlug($Category->getSlug() . $Category->getId());
            } else {
                $Category->setSlug(CrispyHelper::slugify($Category->getName()));
            }
        }

        $Category->computeUrl();

        if ($this->categoryDatabaseController->checkSlugCollision($Category)) {
            $isError = true;
            $fieldErrors[] = 'CMSControl.Views.Categories.Sweetalert.Error.SlugCollision';
        }


        if (RESTfulAPI::BodyParameterExists('properties')) {

            if (is_array($Body['properties'])) {
                $Category->setProperties(array_sum($Body['properties']));
            } else {
                $Category->setProperties($Body['properties']);
            }

            if ($Category->hasProperty(CategoryProperties::VISIBILITY_PRIVATE) && $Category->hasProperty(CategoryProperties::VISIBILITY_PUBLIC)) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Categories.Sweetalert.Error.VisibilityCollision';
            }

            if (!$Category->hasProperty(CategoryProperties::VISIBILITY_PRIVATE) && !$Category->hasProperty(CategoryProperties::VISIBILITY_PUBLIC)) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Categories.Sweetalert.Error.VisibilityMissing';
            }
        }



        if ($isError) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        if (!$this->categoryDatabaseController->updateCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update category to database', [], HTTP: 500);

            return;
        }

        $this->categoryDatabaseController->commitTransaction();


        $this->categoryDatabaseController->beginTransaction();
        $Category->updatePagesRecursive();
        $this->categoryDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Category updated', [
            'category' => $Category->toArray(),
        ], HTTP: 200);
    }

    public function processDELETERequest(int $id): void
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

        if (!$Category->canBeDeleted()) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Category cannot be deleted', [], HTTP: 400);
            return;
        }


        $Pages = $this->pageDatabaseController->fetchAllByCategory($Category);

        foreach ($Pages as $Page) {
            $Page->setCategory(null);
        }



        $this->categoryDatabaseController->beginTransaction();

        if (!$this->categoryDatabaseController->deleteCategory($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete category from database', [], HTTP: 500);

            return;
        }

        $this->categoryDatabaseController->commitTransaction();

        $this->categoryDatabaseController->beginTransaction();
        $Category->updatePagesRecursive();
        $this->categoryDatabaseController->commitTransaction();

        http_response_code(204);
    }

    public function json(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read categories', [], HTTP: 403);
            return;
        }

        if (!$Category = $this->categoryDatabaseController->getCategoryById($id)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($Category->toArray());
    }
}
