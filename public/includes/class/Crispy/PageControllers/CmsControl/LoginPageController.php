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
use crisp\core\Security;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\UserDatabaseController;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class LoginPageController
{

    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }

    public function preRender(): void
    {
        if ($this->userDatabaseController->countAllUsers() === 0) {
            header("Location: /admin/register");
            return;
        }
        
        if (Sessions::isSessionValid() && ThemeVariables::get("User")) {
            header("Location: /admin");
            return;
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->loginUser();
            return;
        }

        echo Themes::render("Views/Login.twig");
    }


    public function loginUser(): void
    {

        if (empty($_POST["csrf"]) || !Security::matchCSRF($_POST["csrf"])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, "Invalid CSRF token", [], HTTP: 400);
            return;
        }

        if (empty($_POST['username']) && !is_string($_POST['username'])) {
            RESTfulAPI::response(Bitmask::MISSING_PARAMETER, 'Invalid parameter "name"', [], HTTP: 400);
            return;
        }

        $User = $this->userDatabaseController->getUserByUsername($_POST['username']);

        if (!$User) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Login.Sweetalert.Error.LoginFailed', [], HTTP: 400);
            return;
        }

        if (!$User->verifyPassword($_POST['password'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Login.Sweetalert.Error.LoginFailed', [], HTTP: 400);
            return;
        }

        if (Sessions::createSession($User->getId()) !== false) {
            Security::regenCSRF();
            RestfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Login successful', [], HTTP: 200);
            return;
        }

        RestfulAPI::response(Bitmask::GENERIC_ERROR, 'Login failed', [], HTTP: 400);
    }
}
