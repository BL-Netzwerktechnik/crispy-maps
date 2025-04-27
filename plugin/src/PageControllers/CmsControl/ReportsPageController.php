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


namespace blfilme\lostplaces\PageControllers\CmsControl;

use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\DatabaseControllers\ReportDatabaseController;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;


class ReportsPageController
{
    private UserController $userController;
    private ReportDatabaseController $reportDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
    ];

    private array $readPermissions = [
        Permissions::SUPERUSER->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->reportDatabaseController = new ReportDatabaseController();
    }

    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Permission denied', [], HTTP: 403);
            return;
        }
        
        $report = $this->reportDatabaseController->getReportById($id);

        if ($report === null) {
            RESTfulAPI::response(Bitmask::QUERY_FAILED, 'Report not found', [], HTTP: 404);
            return;
        }

        $this->reportDatabaseController->beginTransaction();
        if(!$this->reportDatabaseController->deleteReport($report)) {
            $this->reportDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::QUERY_FAILED, 'Failed to delete report', [], HTTP: 500);
            return;
        }
        $this->reportDatabaseController->commitTransaction();
        http_response_code(204);
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

        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));
        ThemeVariables::set("Reports", array_map(fn($category) => $category->toArray(), $this->reportDatabaseController->fetchAllReports()));



        echo Themes::render("lostplaces/templates/Views/CmsControl/Reports.twig");
    }

    public function postRender(): void {}
}
