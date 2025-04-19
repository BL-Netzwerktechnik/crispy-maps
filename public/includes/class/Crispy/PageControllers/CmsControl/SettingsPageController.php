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

use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\Translation;
use crisp\Controllers\EventController;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Events\SettingsTabListCreatedEvent;
use Crispy\EventSubscribers\SettingTabListCreatedEventSubscriber;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use Crispy\Models\RoleModel;
use Crispy\Models\SettingsTabListModel;
use JetBrains\PhpStorm\ArrayShape;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;


class SettingsPageController
{
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value
    ];

    public function __construct()
    {
        $this->userController = new UserController();
    }

    public function testEmailRequest(): void
    {

        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write configs', [], HTTP: 403);
            return;
        }

        $isError = false;
        $errors = [];


        if (!Config::get('CMSControl_Email_Host')) {
            $isError = true;
            $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailHostMissing';
        }

        if (!Config::get('CMSControl_Email_Port')) {
            $isError = true;
            $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailPortMissing';
        }


        if ((!Config::get('CMSControl_Email_Username') && Config::get('CMSControl_Email_Password')) || (!Config::get('CMSControl_Email_Username') && !Config::get('CMSControl_Email_FromEmail'))) {
            $isError = true;
            $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailUsernameMissing';
        }

        if (!Config::get('CMSControl_Email_Password') && Config::get('CMSControl_Email_Username')) {
            $isError = true;
            $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailPasswordMissing';
        }

        if ((!Config::get('CMSControl_Email_Username') && Config::get('CMSControl_Email_FromEmail'))) {
            $isError = true;
            $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailFromEmailMissing';
        }

        if (!$isError) {

            $PHPMailer = new PHPMailer();

            $PHPMailer->isSMTP();

            $PHPMailer->Host = Config::get('CMSControl_Email_Host');
            $PHPMailer->Port = Config::get('CMSControl_Email_Port');
            if (Config::get('CMSControl_Email_Username') && Config::get('CMSControl_Email_Password')) {
                $PHPMailer->SMTPAuth = true;
                $PHPMailer->Username = Config::get('CMSControl_Email_Username');
                $PHPMailer->Password = Config::get('CMSControl_Email_Password');
            }

            $PHPMailer->SMTPSecure = match (Config::get('CMSControl_Email_Secure')) {
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                default => false
            };

            $PHPMailer->setFrom(Config::get('CMSControl_Email_From') ?? Config::get('CMSControl_Email_Username'), Config::get('CMSControl_Email_FromName') ?? Config::get('CMSControl_SiteName') ?? 'CrispyCMS');

            $PHPMailer->addAddress($this->userController->getUser()->getEmail(), $this->userController->getUser()->getName());

            $PHPMailer->Subject = 'Test Email';

            $PHPMailer->Body = 'This is a test email from CrispyCMS.';

            try {
                if(!$PHPMailer->send()) {
                    $isError = true;
                    $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailSendFailed';
                    $errors[] = $PHPMailer->ErrorInfo;
                    Logger::getLogger(__METHOD__)->error($PHPMailer->ErrorInfo);
                }
            } catch (\Exception $e) {
                $isError = true;
                $errors[] = 'CMSControl.Views.Settings.Sweetalert.Error.EmailSendFailed';
                $errors[] = $e->getMessage();
                Logger::getLogger(__METHOD__)->error($e->getMessage());
            }
        }


        if ($isError) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Email test failed', [
                "errors" => $errors
            ], HTTP: 400);
            return;
        }

        http_response_code(201);
    }

    public function processPUTRequest(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write configs', [], HTTP: 403);
            return;
        }

        $Body = RESTfulAPI::getBody();

        foreach ($Body as $key => $value) {
            Config::set($key, $value);
        }


        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Role updated', $Body, HTTP: 200);
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

        EventController::getEventDispatcher()->addSubscriber(new SettingTabListCreatedEventSubscriber());
        EventController::getEventDispatcher()->dispatch(new SettingsTabListCreatedEvent(SettingsTabListModel::getTabList()));

        ThemeVariables::set('settingsTabList', SettingsTabListModel::getTabList()->getItems());

        echo Themes::render("Views/Settings.twig");
    }


    public function postRender(): void {}
}
