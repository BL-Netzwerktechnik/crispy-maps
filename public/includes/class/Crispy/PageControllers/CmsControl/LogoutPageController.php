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
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\UserDatabaseController;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class LogoutPageController
{
    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }

    public function preRender(): void
    {
        if (!Sessions::isSessionValid() && !ThemeVariables::get("User")) {
            header("Location: /admin/login");
            return;
        }
        Sessions::destroyCurrentSession($_SESSION['crisp_session_login']["user"]);
        header("Location: /admin/login");
    }

    public function postRender(): void {}
}
