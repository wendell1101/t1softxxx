<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ipm_v2_esports_game_logs_20220530 extends CI_Migration {

    private $tableName = 'ipm_v2_esports_game_logs';

    public function up() {
        $field = array(
            "FlagCategory" => array(
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('FlagCategory', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('FlagCategory', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'FlagCategory');
            }
        }
    }
}