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
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use Crispy\Models\RoleModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class RolesPageController
{
    private RoleDatabaseController $roleDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_ROLES->value,
        Permissions::WRITE_ROLES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_ROLES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->roleDatabaseController = new RoleDatabaseController();
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

        $this->roleDatabaseController->beginTransaction();

        $Role = new RoleModel(
            name: $_POST['name'],
            permissions: 0
        );

        if (!$this->roleDatabaseController->insertRole($Role)) {
            $this->roleDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create role in database', [], HTTP: 500);

            return;
        }

        $this->roleDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Role created', [
            'role' => $Role->toArray(),
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

        ThemeVariables::set("Permissions", Permissions::cases());
        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));


        echo Themes::render("Views/Roles.twig");
    }

    public function json(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }
        
        if(!$this->userController->checkPermissionStack($this->readPermissions)){
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read roles', [], HTTP: 403);
            return;
        }

        header('Content-Type: application/json');
        echo $this->roleDatabaseController->generateJsTreeJson();
    }



    public function postRender(): void {}
}
