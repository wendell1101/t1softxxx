<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_go_live_to_game_api_update_history_20200731 extends CI_Migration
{
    private $tableName = 'game_api_update_history';
    public function up() {

        $fields = array(
            'go_live' => array(
                'type' => 'TINYINT',
                'default' => 0,
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('go_live', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }

    }

    public function down() {
        if($this->db->field_exists('go_live', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'go_live');
        }
    }
}