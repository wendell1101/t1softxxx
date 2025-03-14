<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_game_api_update_history_20230516 extends CI_Migration
{
	private $tableName = 'game_api_update_history';

    public function up() {

        $field = array(
            'original_game_platform_id' => array(
                'type' => 'INT',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('original_game_platform_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('original_game_platform_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'original_game_platform_id');
            }
        }
    }
}