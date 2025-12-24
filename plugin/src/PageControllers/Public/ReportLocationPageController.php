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
use blfilme\lostplaces\DatabaseControllers\ReportDatabaseController;
use blfilme\lostplaces\Enums\ReportReasons;
use blfilme\lostplaces\Models\ReportModel;
use crisp\api\Helper;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use Crispy\Controllers\UserController;

class ReportLocationPageController
{
    private UserController $userController;
    private LocationDatabaseController $locationDatabaseController;
    private ReportDatabaseController $reportDatabaseController;

    public function __construct()
    {
        $this->userController = new UserController();
        $this->reportDatabaseController = new ReportDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
    }

    public function processPOSTRequest(int $id): void
    {
        $Location = $this->locationDatabaseController->getLocationById($id);

        if ($Location === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Location not found', [], HTTP: 404);

            return;
        }

        $this->reportDatabaseController->beginTransaction();

        if (Sessions::isSessionValid()) {
            $hasReported = $this->reportDatabaseController->reportExistsByLocationAndUser($Location, $this->userController->getUser());
        } else {
            $hasReported = $this->reportDatabaseController->reportExistByLocationAndIpAddress($Location, Helper::getRealIpAddr());
        }

        if ($hasReported) {
            // Report already exists, silently ignore
            http_response_code(201);

            return;
        }

        $reasons = [];

        if (isset($_POST['reason']) && is_array($_POST['reason'])) {
            foreach ($_POST['reason'] as $reason) {
                if (is_numeric($reason) && ReportReasons::tryFrom($reason) !== null) {
                    ;
                    $reasons[] = ReportReasons::tryFrom($reason);
                }
            }
        }

        Logger::getLogger(__METHOD__)->debug('Processing POST request', [
            'reason' => $reasons,
            'reasonPOST' => $_POST['reason'] ?? '',
            'description' => $_POST['description'] ?? '',
        ]);

        if (empty($reasons)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "reasons"', [], HTTP: 400);

            return;
        }

        $reportModel = new ReportModel(
            id: null,
            location: $Location,
            user: Sessions::isSessionValid() ? $this->userController->getUser() : null,
            ipAddress: Sessions::isSessionValid() ? null : Helper::getRealIpAddr(),
            description: $_POST['description'] ?? '',
            reasons: $reasons,
        );

        if (!$this->reportDatabaseController->insertReport($reportModel)) {
            $this->reportDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to Report', [], HTTP: 500);

            return;
        }
        $this->reportDatabaseController->commitTransaction();

        http_response_code(201);

        return;
    }
}
