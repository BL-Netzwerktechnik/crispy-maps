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
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\PageProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class PagePageController
{
    private TemplateDatabaseController $templateDatabaseController;
    private PageDatabaseController $pageDatabaseController;
    private UserController $userController;
    private TemplateGeneratorController $templateGeneratorController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_PAGES->value,
        Permissions::WRITE_PAGES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];



    public function __construct()
    {
        $this->pageDatabaseController = new PageDatabaseController();
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->userController = new UserController();
        $this->templateGeneratorController = new TemplateGeneratorController();
    }

    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read or write pages', [], HTTP: 403);
            return;
        }

        $Body = RESTfulAPI::getBody();

        $isError = false;
        $fieldErrors = [];

        if (is_string($Body) || empty($Body) || is_null($Body)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid JSON in body', []);
            exit;
        }


        if (!$Page = $this->pageDatabaseController->getPageById($id)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Page not found', [], HTTP: 404);
            return;
        }

        $this->pageDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('name') && !is_string($Body['name'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "name"';
        } elseif (RESTfulAPI::BodyParameterExists('name')) {
            $Page->setName($Body['name']);
        }


        if (RESTfulAPI::BodyParameterExists('content') && !is_string($Body['content'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "content"';
        } elseif (RESTfulAPI::BodyParameterExists('content')) {
            $Page->setContent($Body['content']);
        }


        if (RESTfulAPI::BodyParameterExists('slug') && !is_string($Body['slug'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "slug"';
        } elseif (RESTfulAPI::BodyParameterExists('slug') && !empty($Body['slug'])) {

            $Page->setSlug(CrispyHelper::slugify($Body['slug']));
        } else {
            if ($this->pageDatabaseController->getPageBySlug($Page->getSlug())) {
                $Page->setSlug($Page->getSlug() . $Page->getId());
            } else {
                $Page->setSlug(CrispyHelper::slugify($Page->getName()));
            }
        }

        $Page->computeUrl();

        if ($this->pageDatabaseController->checkSlugCollision($Page)) {
            $isError = true;
            $fieldErrors[] = 'CMSControl.Views.Pages.Sweetalert.Error.SlugCollision';
        }


        if (RESTfulAPI::BodyParameterExists('properties')) {

            if (is_array($Body['properties'])) {
                $Page->setProperties(array_sum($Body['properties']));
            } else {
                $Page->setProperties($Body['properties']);
            }

            if ($Page->hasProperty(PageProperties::VISIBILITY_PRIVATE) && $Page->hasProperty(PageProperties::VISIBILITY_PUBLIC)) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Pages.Sweetalert.Error.VisibilityCollision';
            }

            
            if ($Page->hasProperty(PageProperties::OPTION_FRONTPAGE) && $Page->hasProperty(PageProperties::OPTION_NOT_FOUND)) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Pages.Sweetalert.Error.FrontpageNotFoundCollision';
            }

            if (!$Page->hasProperty(PageProperties::VISIBILITY_PRIVATE) && !$Page->hasProperty(PageProperties::VISIBILITY_PUBLIC)) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Pages.Sweetalert.Error.VisibilityMissing';
            }


            if($Page->hasProperty(PageProperties::OPTION_FRONTPAGE) ) {
                foreach ($this->pageDatabaseController->fetchAllPagesByProperty(PageProperties::OPTION_FRONTPAGE) as $Frontpage) {
                    $Frontpage->removeProperty(PageProperties::OPTION_FRONTPAGE);
                    $this->pageDatabaseController->updatePage($Frontpage);
                }
            }
            
            if($Page->hasProperty(PageProperties::OPTION_NOT_FOUND) ) {
                foreach ($this->pageDatabaseController->fetchAllPagesByProperty(PageProperties::OPTION_NOT_FOUND) as $NotFoundPage) {
                    $NotFoundPage->removeProperty(PageProperties::OPTION_NOT_FOUND);
                    $this->pageDatabaseController->updatePage($NotFoundPage);
                }
            }
        }


        if (RESTfulAPI::BodyParameterExists('template')) {
            if (!$Template = $this->templateDatabaseController->getTemplateById((int)$Body['template'])) {
                $isError = true;
                $fieldErrors[] = 'Invalid JSON "template"';
            } else {
                $Page->setTemplate($Template);
            }
        }



        if ($isError) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        if (!$this->pageDatabaseController->updatePage($Page)) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update page to database', [], HTTP: 500);

            return;
        }

        $this->pageDatabaseController->commitTransaction();

        $this->templateGeneratorController->generate();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Page updated', [
            'page' => $Page->toArray(),
        ], HTTP: 200);
    }

    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write pages', [], HTTP: 403);
            return;
        }

        if (!$Page = $this->pageDatabaseController->getPageById($id)) {
            http_response_code(404);
            return;
        }


        $this->pageDatabaseController->beginTransaction();

        if (!$this->pageDatabaseController->deletePage($Page)) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete page from database', [], HTTP: 500);

            return;
        }

        $this->pageDatabaseController->commitTransaction();

        http_response_code(204);
    }

    public function json(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read pages', [], HTTP: 403);
            return;
        }

        if (!$Page = $this->pageDatabaseController->getPageById($id)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($Page->toArray());
    }
}
