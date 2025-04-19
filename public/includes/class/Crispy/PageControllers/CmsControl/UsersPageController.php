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
use Crispy\Models\UserModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class UsersPageController
{
    private RoleDatabaseController $roleDatabaseController;
    private UserDatabaseController $userDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_USERS->value,
        Permissions::WRITE_USERS->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_USERS->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->roleDatabaseController = new RoleDatabaseController();
        $this->userDatabaseController = new UserDatabaseController();
    }


    public function createNewUser(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write users', [], HTTP: 403);
            return;
        }


        if (empty($_POST['name'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "name"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['username'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "username"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['email'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "email"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['password'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "password"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['role'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "role"', [], HTTP: 400);
            return;
        }

        if (!$Role = $this->roleDatabaseController->getRoleById($_POST['role'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Role does not exist', [], HTTP: 400);
            return;
        }

        if ($this->userDatabaseController->getUserByUsername($_POST['username'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Username already exists', [], HTTP: 400);
            return;
        }

        if ($this->userDatabaseController->getUserByEmail($_POST['email'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Email already exists', [], HTTP: 400);
            return;
        }

        $this->userDatabaseController->beginTransaction();

        $User = new UserModel(
            name: $_POST['name'],
            username: $_POST['username'],
            email: $_POST['email'],
            emailVerified: true,
            role: $Role
        );

        $User->setPassword($_POST['password']);

        if (!$this->userDatabaseController->insertUser($User)) {
            $this->userDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create user in database', [], HTTP: 500);

            return;
        }

        $this->userDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'User created', [
            'user' => $User->toArray(),
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

        ThemeVariables::set("Roles", array_map(fn($role) => $role->toArray(), $this->roleDatabaseController->fetchAllRoles()));
        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));


        echo Themes::render("Views/Users.twig");
    }

    public function json(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }
        
        if(!$this->userController->checkPermissionStack($this->readPermissions)){
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read users', [], HTTP: 403);
            return;
        }

        header('Content-Type: application/json');
        echo $this->roleDatabaseController->generateJsTreeJson(true);
    }



    public function postRender(): void {}
}
