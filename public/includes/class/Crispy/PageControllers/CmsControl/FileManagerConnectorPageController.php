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
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Security;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\Permissions;
use elFinder;
use elFinderConnector;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class FileManagerConnectorPageController
{

    private UserController $userController;

    private array $forbiddenMimeTypes = [
        "application/x-php",
        "application/x-httpd-php",
        "application/x-httpd-php-source",
        "text/php",
        "text/x-php",
        "text/x-httpd-php",
        "text/x-httpd-php-source",
    ];

    private array $allowedMimeTypes =  [
        "all",
        // Images
        'image/x-ms-bmp',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/x-icon',
        'image/webp',
        'image/svg+xml',
        'image/tiff',

        // Documents
        'text/plain',
        'application/pdf',
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-powerpoint', // .ppt
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
        'application/rtf', // .rtf
        'application/vnd.oasis.opendocument.text', // .odt
        'application/vnd.oasis.opendocument.spreadsheet', // .ods
        'application/vnd.oasis.opendocument.presentation', // .odp

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-7z-compressed',

        // Audio
        'audio/mpeg', // .mp3
        'audio/ogg', // .ogg
        'audio/wav', // .wav
        'audio/x-ms-wma', // .wma

        // Video
        'video/mp4',
        'video/x-msvideo', // .avi
        'video/x-ms-wmv', // .wmv
        'video/quicktime', // .mov
        'video/webm',
        'video/ogg',

        // Fonts
        'font/ttf',
        'font/woff',
        'font/woff2',

        // JSON, XML, CSV
        'application/json',
        'application/xml',
        'text/csv'

    ];

    public function __construct()
    {
        $this->userController = new UserController();


        if (!file_exists(core::PERSISTENT_DATA . '/files/')) {
            mkdir(core::PERSISTENT_DATA . '/files/', 0777, true);
            chown(core::PERSISTENT_DATA . '/files/', 'www-data');
        }
    }

    private function access($attr, $path, $data, $volume, $isDir, $relpath)
    {
        $basename = basename($path);
        return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
            && strlen($relpath) !== 1           // but with out volume root
            ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
            :  null;                                 // else elFinder decide it itself
    }

    public function preRender(): void
    {

        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack([Permissions::SUPERUSER->value, Permissions::READ_FILES->value, Permissions::WRITE_FILES->value])) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read files', [], HTTP: 403);
            return;
        }


        $opts = array(
            'roots' => array(
                // Items volume
                array(
                    'driver'        => 'LocalFileSystem',
                    'path'          => core::PERSISTENT_DATA . '/files/',
                    'URL'           => sprintf("%s://%s/assets/ugc/", $_ENV["PROTO"], $_ENV["HOST"]),
                    'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny'    => $this->forbiddenMimeTypes,
                    'uploadAllow'   => $this->allowedMimeTypes,
                    'uploadOrder'   => array('deny', 'allow'),
                    'accessControl' => 'access',
                    'attributes' => array(
                        array(
                            'pattern' => '/^.+/',
                            'read'    => $this->userController->checkPermissionStack([Permissions::SUPERUSER->value, Permissions::READ_FILES->value, Permissions::WRITE_FILES->value]),
                            'write'   => $this->userController->checkPermissionStack([Permissions::SUPERUSER->value, Permissions::WRITE_FILES->value]),
                            //'locked'  => $this->userController->checkPermissionStack([Permissions::SUPERUSER->value, Permissions::WRITE_FILES->value]),
                        )
                    )
                )
            )
        );


        // run elFinder
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    }
}
