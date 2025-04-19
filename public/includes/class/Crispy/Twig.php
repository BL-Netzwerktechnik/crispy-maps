<?php

namespace Crispy;


class Twig {

    public static function generate(array $input): string {

        $html = "<ul>";

        foreach($input as $item){
            $html .= "<li>" . $item . "</li>";
        }

        $html .= "</ul>";

        return $html;

    }

}