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

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Models\CategoryModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;
use GeoIp2\Record\Location;

class EditLocationPageController
{
    private UserController $userController;
    private LocationDatabaseController $locationDatabaseController;
    private CategoryDatabaseController $categoryDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
    }



    public function processPOSTRequest(int $id): void
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


        if (empty($_POST['description'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "description"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['category'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "category"', [], HTTP: 400);
            return;
        }

        if (empty($_POST['status'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "status"', [], HTTP: 400);
            return;
        }

        $Location = $this->locationDatabaseController->getLocationById($id);

        if ($Location === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Location not found', [], HTTP: 404);
            return;
        }

        $Category = $this->categoryDatabaseController->getCategoryById((int)$_POST['category']);

        if ($Category === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Category not found', [], HTTP: 404);
            return;
        }

        // properties is an int array, convert it to an array of LocationProperties

        $convertedProperties = [];

        if (is_array($_POST['properties'])) {

            foreach ($_POST['properties'] as $property) {
                if (!LocationProperties::tryFrom($property)) {
                    RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid property', [], HTTP: 400);
                    return;
                }
                $convertedProperties[] = LocationProperties::from($property);
            }
        }else{
            $convertedProperties = LocationProperties::fromIntToArray((int)$_POST['properties']);
        }

        $Location->setName($_POST['name']);
        $Location->setDescription($_POST['description']);
        $Location->setYoutube($_POST['youtube'] ?? null);
        $Location->setProperties($convertedProperties);
        $Location->setStatus(LocationStatus::from($_POST['status']));
        $Location->setCategory($Category);

        $this->locationDatabaseController->beginTransaction();
        if (!$this->locationDatabaseController->updateLocation($Location)) {
            $this->locationDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update Location', [], HTTP: 500);
            return;
        }
        $this->locationDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Location updated successfully', [], HTTP: 200);
    }

    public function preRender(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            header("Location: /admin/login");
            return;
        }

        if (!$this->userController->checkPermissionStack($this->writePermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }


        $Location = $this->locationDatabaseController->getLocationById($id);

        if ($Location === null) {
            ThemeVariables::set("ErrorMessage", "Location nicht gefunden");
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("Location", $Location->toArray());
        ThemeVariables::set("Categories", array_map(function (CategoryModel $category) {
            return $category->toArray();
        }, $this->categoryDatabaseController->fetchAllCategories()));


        ThemeVariables::set("Statuses", LocationStatus::cases());
        ThemeVariables::set("Properties", LocationProperties::cases());
        echo Themes::render("lostplaces/templates/Views/CmsControl/LocationForm.twig");
    }
}
