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


namespace crisp\migrations;

class addlayouts extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("crispy_layouts",
                array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                array("name", $this::DB_VARCHAR, "NOT NULL"),
                array("content", $this::DB_TEXT, "DEFAULT NULL"),
                array("author", $this::DB_BIGINT, "NOT NULL"),
                array("slug", $this::DB_VARCHAR, "NOT NULL"),
                array('created_at', $this::DB_TIMESTAMP, 'NOT NULL DEFAULT CURRENT_TIMESTAMP'),
                array('updated_at', $this::DB_TIMESTAMP, 'NULL'),


            );
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
