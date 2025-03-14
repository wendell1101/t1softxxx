<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_game_api_update_history_20231101 extends CI_Migration
{
	private $tableName = 'game_api_update_history';

    public function up() {

        $field = array(
            'attributes' => array(
                'type' => 'JSON',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('attributes', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('attributes', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'attributes');
            }
        }
    }
}