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
use crisp\models\ThemePage;
use crisp\core\Themes;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class TestPageController {
    public function preRender(): void
    {
        echo Themes::render("Base.twig");        
    }

    public function postRender(): void
    {
        
    }
}