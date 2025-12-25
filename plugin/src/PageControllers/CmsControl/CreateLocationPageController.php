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
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Models\CategoryModel;
use blfilme\lostplaces\Models\CoordinateModel;
use blfilme\lostplaces\Models\LocationModel;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;

class CreateLocationPageController
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

    public function processPOSTRequest(): void
    {
        $this->userController->helperValidateBackendAccess(true);

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

        // Normalize external_link: empty string becomes null
        $externalLink = isset($_POST['external_link']) && $_POST['external_link'] !== '' ? $_POST['external_link'] : null;

        if ($externalLink !== null && !filter_var($externalLink, FILTER_VALIDATE_URL)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid URL format for "external_link"', [], HTTP: 400);

            return;
        }

        $Category = $this->categoryDatabaseController->getCategoryById((int) $_POST['category']);

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
        } else {
            $convertedProperties = LocationProperties::fromIntToArray((int) $_POST['properties']);
        }

        $Location = new LocationModel(
            id: null,
            name: $_POST['name'],
            description: $_POST['description'],
            youtube: $_POST['youtube'] ?? null,
            properties: $convertedProperties,
            category: $Category,
            status: LocationStatus::from($_POST['status']),
            coordinates: new CoordinateModel($_GET['lat'], $_GET['lng']),
            author: $this->userController->getUser(),
            externalLink: $externalLink,
        );

        $this->locationDatabaseController->beginTransaction();
        if (!$this->locationDatabaseController->insertLocation($Location)) {
            $this->locationDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to insert Location', [], HTTP: 500);

            return;
        }
        $this->locationDatabaseController->commitTransaction();
        $Location->createFolderStructure();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Location inserted successfully', [], HTTP: 200);
    }

    public function preRender(): void
    {
        $this->userController->helperValidateBackendAccess(false);

        if (!$this->userController->checkPermissionStack($this->writePermissions)) {

            ThemeVariables::set('ErrorMessage', Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render('Views/ErrorPage.twig');

            return;
        }

        ThemeVariables::set('Categories', array_map(function (CategoryModel $category) {
            return $category->toArray();
        }, $this->categoryDatabaseController->fetchAllCategories()));

        ThemeVariables::set('Statuses', LocationStatus::cases());
        ThemeVariables::set('Properties', LocationProperties::cases());
        ThemeVariables::set('hideMap', true);
        echo Themes::render('maps/templates/Views/CmsControl/LocationForm.twig');
    }
}
