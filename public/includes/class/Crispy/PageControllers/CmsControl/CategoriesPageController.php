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


class CategoriesPageController
{
    private CategoryDatabaseController $categoryDatabaseController;
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

        if (!empty($_POST['parent']) && !$Parent = $this->categoryDatabaseController->getCategoryById($_POST['parent'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "parent"', [], HTTP: 400);
            return;
        }


        if (empty($_POST['slug'])) {
            $_POST['slug'] = CrispyHelper::slugify($_POST['name']);
        }

        if (empty($_POST['properties']) || !is_array($_POST['properties'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Categories.Sweetalert.Error.VisibilityMissing', [], HTTP: 400);
            return;
        }

        $this->categoryDatabaseController->beginTransaction();

        $Category = new CategoryModel(
            name: $_POST['name'],
            slug: CrispyHelper::slugify($_POST['name']),
            properties: array_sum($_POST['properties']),
            parent: $Parent ?? null
        );


        if ($Category->hasProperty(CategoryProperties::VISIBILITY_PRIVATE) && $Category->hasProperty(CategoryProperties::VISIBILITY_PUBLIC)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Categories.Sweetalert.Error.VisibilityCollision', [], HTTP: 400);
            return;
        }

        if (!$Category->hasProperty(CategoryProperties::VISIBILITY_PRIVATE) && !$Category->hasProperty(CategoryProperties::VISIBILITY_PUBLIC)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Categories.Sweetalert.Error.VisibilityMissing', [], HTTP: 400);
            return;
        }

        
        if($this->categoryDatabaseController->checkSlugCollision($Category)){
            $Category->setSlug($Category->getSlug() . '-' . rand(1000, 9999));
        }
        $Category->computeUrl();


        if (!$this->categoryDatabaseController->insertCategories($Category)) {
            $this->categoryDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create category in database', [], HTTP: 500);

            return;
        }

        $this->categoryDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Category created', [
            'category' => $Category->toArray(),
        ], HTTP: 201);
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

        ThemeVariables::set("CategoryProperties", CategoryProperties::cases());
        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));


        echo Themes::render("Views/Categories.twig");
    }

    public function json(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }
        
        if(!$this->userController->checkPermissionStack($this->readPermissions)){
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read categories', [], HTTP: 403);
            return;
        }

        header('Content-Type: application/json');


        

        if ($_GET["format"] == "select2") {

            foreach ($this->categoryDatabaseController->fetchAllCategories() as $category) {
                $data[] = [
                    "id" => $category->getId(),
                    "text" => sprintf("[%s] %s", $category->getComputedUrl(), $category->getName())
                ];
            }

            echo json_encode([

                "results" => $data
            ]);

            return;
        }
        echo $this->categoryDatabaseController->generateJsTreeJson(false);
    }



    public function postRender(): void {}
}
