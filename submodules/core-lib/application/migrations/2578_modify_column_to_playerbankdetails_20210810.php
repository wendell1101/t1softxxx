<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_modify_column_to_playerbankdetails_20210810 extends CI_Migration{

    private $tableName = "playerbankdetails";

    public function up() {
        $column = array(
            "pixKey" => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                "null" => true
            )
        );

        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->modify_column($this->tableName, $column);
        }
    }

    public function down() {

    }
}