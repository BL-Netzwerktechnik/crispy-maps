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


namespace Crispy\PageControllers;

use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\PageProperties;
use Crispy\FileControllers\TemplateFileController;
use Crispy\Models\PageModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class RenderPageController
{


    private UserDatabaseController $userDatabaseController;
    private PageDatabaseController $pageDatabaseController;
    private UserController $userController;

    public function __construct()
    {
        $this->pageDatabaseController = new PageDatabaseController();
        $this->userDatabaseController = new UserDatabaseController();
        $this->userController = new UserController();
    }

    public function renderNotFoundPage(): void
    {
        $NotFoundPage = $this->pageDatabaseController->fetchAllPagesByProperty(PageProperties::OPTION_NOT_FOUND)[0];
        if ($NotFoundPage) {
            $this->renderDynamicPage($NotFoundPage, true);
        } else {
            echo Themes::render("templates/errors/notfound.twig", core::THEME_BASE_DIR . "/basic");
            return;
        }
    }

    public function renderDynamicPage(?PageModel $page): void
    {
        if (!$page) {
            $this->renderNotFoundPage();
            return;
        }
        if ($page->isPrivate() && !$this->userController->isSessionValid()) {
            $this->renderNotFoundPage();
            return;
        }

        ThemeVariables::set("Page", $page->toArray());
        echo Themes::render($page->getTemplate()->getFrontendCodePath(), core::THEME_BASE_DIR . "/build");
    }


    public function preRender(?string $computedUrl = null): void
    {
        if ($this->userDatabaseController->countAllUsers() === 0) {
            header("Location: /admin/register");
            return;
        }

        if ($this->pageDatabaseController->countAllPages() === 0) {
            ThemeVariables::set("AlertText", Translation::fetch("CMSControl.Views.ErrorPageNoLayoutWrapper.AlertText.NoPages"));
            echo Themes::render("Views/ErrorPageNoLayoutWrapper.twig");
            return;
        }

        if (!$computedUrl) {
            $page = $this->pageDatabaseController->fetchFrontpagePage();
        } else {
            $page = $this->pageDatabaseController->getPageByComputedUrl($computedUrl);
        }

        $this->renderDynamicPage($page);
    }

    public function postRender(): void {}
}
