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

namespace blfilme\lostplaces\PageControllers\Public;

use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\DatabaseControllers\VoteDatabaseController;
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\ReportReasons;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Sessions;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Enums\Permissions;

class LocationRenderPageController
{
    private UserController $userController;
    private TemplateDatabaseController $templateDatabaseController;
    private LocationDatabaseController $locationDatabaseController;
    private VoteDatabaseController $voteDatabaseController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];

    public function __construct()
    {
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->userController = new UserController();
        $this->locationDatabaseController = new LocationDatabaseController();
        $this->voteDatabaseController = new VoteDatabaseController();
    }

    public function preRender(int $id): void
    {

        if (Sessions::isSessionValid()) {
            ThemeVariables::set('HasWritePermission', $this->userController->checkPermissionStack($this->writePermissions));
        }
        if (!Config::exists('LostPlaces_LocationTemplate')) {
            ThemeVariables::set('ErrorMessage', 'Die Vorlage für die Location-Seite ist nicht gesetzt. Bitte setze die Vorlage in den Plugin-Einstellungen.');
            echo Themes::render('Views/ErrorPage.twig');

            return;
        }

        $Template = $this->templateDatabaseController->getTemplateById(Config::get('LostPlaces_LocationTemplate'));
        if ($Template === null) {
            ThemeVariables::set('ErrorMessage', 'Die Vorlage für die Location-Seite existiert nicht. Bitte setze die Vorlage in den Plugin-Einstellungen.');
            echo Themes::render('Views/ErrorPage.twig');

            return;
        }

        if (!$Location = $this->locationDatabaseController->getLocationById($id)) {
            header(('Location: /404'));

            return;
        }

        ThemeVariables::set('Location', $Location->toArray());
        ThemeVariables::set('AllLocationProperties', LocationProperties::cases());
        ThemeVariables::set('ReportReasons', ReportReasons::cases());
        ThemeVariables::Set('NearbyLocations', array_map(
            fn ($location) => $location->toArray(),
            $this->locationDatabaseController->fetchNearestLocations($Location, 5, 100)
        ));
        ThemeVariables::set('PropertyBadgeShowLabels', true);
        ThemeVariables::set('hasUpVoted', Sessions::isSessionValid() ? $this->voteDatabaseController->upVoteExistsByLocationAndUser(
            $Location,
            $this->userController->getUser()
        ) : $this->voteDatabaseController->upVoteExistsByLocationAndIpAddress(
            $Location,
            Helper::getRealIpAddr()
        ));

        ThemeVariables::set('hasDownVoted', Sessions::isSessionValid() ? $this->voteDatabaseController->downVoteExistsByLocationAndUser(
            $Location,
            $this->userController->getUser()
        ) : $this->voteDatabaseController->downVoteExistsByLocationAndIpAddress(
            $Location,
            Helper::getRealIpAddr()
        ));

        ThemeVariables::set('upVoteCount', $this->voteDatabaseController->countAllUpVotesForLocation($Location));
        ThemeVariables::set('downVoteCount', $this->voteDatabaseController->countAllDownVotesForLocation($Location));

        echo Themes::render($Template->getFrontendCodePath(), [core::THEME_BASE_DIR . '/build', '/plugins']);
    }
}
