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

use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\Crypto;
use crisp\core\RESTfulAPI;
use crisp\core\Security;
use crisp\core\Sessions;
use crisp\core\Theme;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\TokenDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\AccessTokens;
use Crispy\Models\TokenModel;
use Crispy\Models\UserModel;
use JetBrains\PhpStorm\ArrayShape;
use League\OAuth2\Client\Token\AccessToken;
use Twig\Environment;


class RegisterPageController
{

    private UserDatabaseController $userDatabaseController;
    private RoleDatabaseController $roleDatabaseController;
    private TokenDatabaseController $tokenDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
        $this->roleDatabaseController = new RoleDatabaseController();
        $this->tokenDatabaseController = new TokenDatabaseController();
    }

    public function preRender(): void
    {

        if (Sessions::isSessionValid() && ThemeVariables::get("User")) {
            header("Location: /admin");
            return;
        }

        $totalUsers = $this->userDatabaseController->countAllUsers();
        if ($totalUsers === 0) {
            ThemeVariables::set("IsFirstUser", true);
        }



        if (!Config::get("CMSControl_RegisterEnabled") && $totalUsers > 0 && empty($_GET['token'])) {
            ThemeVariables::set("AlertText", Translation::fetch("CMSControl.Views.Register.Alert.Disabled"));
            echo Themes::render("Views/ErrorPageNoLayoutWrapper.twig");
            return;
        }

        $token = null;

        if (!empty($_GET['token'])) {
            $token = $this->tokenDatabaseController->getTokenByToken($_GET['token']);

            if (!$token) {
                ThemeVariables::set("AlertText", Translation::fetch("CMSControl.Views.Register.Alert.TokenInvalid"));
                echo Themes::render("Views/ErrorPageNoLayoutWrapper.twig");
                return;
            }

            ThemeVariables::set("Token", $token->toArray());
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->registerUser($token);
            return;
        }

        echo Themes::render("Views/Register.twig");
    }

    public function registerUser(?TokenModel $Token = null): void
    {

        if (empty($_POST["csrf"]) || !Security::matchCSRF($_POST["csrf"])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, "Invalid CSRF token", [], HTTP: 400);
            return;
        }

        if (!$Token) {

            if (empty($_POST['name']) && !is_string($_POST['name'])) {
                RESTfulAPI::response(Bitmask::MISSING_PARAMETER, 'Invalid parameter "name"', [], HTTP: 400);
                return;
            }

            if (empty($_POST['email']) && !is_string($_POST['email'])) {
                RESTfulAPI::response(Bitmask::MISSING_PARAMETER, 'Invalid parameter "email"', [], HTTP: 400);
                return;
            }

            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.EmailInvalid', [], HTTP: 400);
                return;
            }

            if (empty($_POST['username']) && !is_string($_POST['username'])) {
                RESTfulAPI::response(Bitmask::MISSING_PARAMETER, 'Invalid parameter "username"', [], HTTP: 400);
                return;
            }


            if (strlen($_POST['username']) < 4 && $Token === null) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.UsernameLength', [], HTTP: 400);
                return;
            }

            if (strlen($_POST['username']) > 20 && $Token === null) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.UsernameLengthMax', [], HTTP: 400);
                return;
            }

            if (strlen($_POST['name']) > 50 && $Token === null) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.NameLengthMax', [], HTTP: 400);
                return;
            }


            if ($this->userDatabaseController->getUserByUsername($_POST['username']) && $Token === null) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.UsernameTaken', [], HTTP: 400);
                return;
            }

            if ($this->userDatabaseController->getUserByEmail($_POST['email']) && $Token === null) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.EmailTaken', [], HTTP: 400);
                return;
            }
        }

        if (empty($_POST['password']) && !is_string($_POST['password'])) {
            RESTfulAPI::response(Bitmask::MISSING_PARAMETER, 'Invalid parameter "password"', [], HTTP: 400);
            return;
        }

        if (strlen($_POST['password']) < 6) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.PasswordLength', [], HTTP: 400);
            return;
        }


        if ($_POST['password'] !== $_POST['password_confirm']) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Register.Sweetalert.Error.PasswordMismatch', [], HTTP: 400);
            return;
        }

        $this->userDatabaseController->beginTransaction();





        $UserModel = $Token ? $Token->getUser() : new UserModel(
            username: $_POST['username'],
            name: $_POST['name'],
            email: $_POST['email'],
            emailVerified: false,
            role: ThemeVariables::get("IsFirstUser") ? $this->roleDatabaseController->getRoleById(1) : $this->roleDatabaseController->getRoleById(3)
        );

        if ($Token) {
            $UserModel->setEmailVerified(true);
        }

        $UserModel->setPassword($_POST['password']);

        if ($Token ? $this->userDatabaseController->updateUser($UserModel) : $this->userDatabaseController->insertUser($UserModel)) {
            if ($Token) {
                $this->tokenDatabaseController->deleteToken($Token);
            }
            $this->userDatabaseController->commitTransaction();
            Security::regenCSRF();
            RESTfulAPI::response(Bitmask::REQUEST_SUCCESS->value, 'User registered', [], HTTP: 200);
        } else {
            $this->userDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Failed to register user', [], HTTP: 500);
        }
    }
}
