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
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class DashboardPageController
{
    private UserDatabaseController $userDatabaseController;
    private PageDatabaseController $pageDatabaseController;
    private LayoutDatabaseController $layoutDatabaseController;
    private TemplateDatabaseController $templateDatabaseController;
    private CategoryDatabaseController $categoryDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
        $this->pageDatabaseController = new PageDatabaseController();
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
    }

    public function preRender(): void
    {
        if (!Sessions::isSessionValid() && !ThemeVariables::get("User")) {
            header("Location: /admin/login");
            return;
        }


        ThemeVariables::setMultiple([
            "Statistics" => [
                "Pages" => $this->pageDatabaseController->countAllPages(),
                "Layouts" => $this->layoutDatabaseController->countAllLayouts(),
                "Templates" => $this->templateDatabaseController->countAllTemplates(),
                "Users" => $this->userDatabaseController->countAllUsers(),
                "Categories" => $this->categoryDatabaseController->countAllCategories()
            ]
        ]);


        echo Themes::render("Views/Dashboard.twig");
    }

    public function postRender(): void {}
}
