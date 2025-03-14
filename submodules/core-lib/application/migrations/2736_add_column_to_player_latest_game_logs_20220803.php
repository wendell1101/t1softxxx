<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_player_latest_game_logs_20220803 extends CI_Migration
{
	private $tableName = 'player_latest_game_logs';

    public function up() {
        $field1 = array(
            'game_description_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_description_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }

    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_description_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_description_id');
            }
        }
    }
}