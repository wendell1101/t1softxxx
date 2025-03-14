<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_cur_score_to_le_gaming_game_logs_20200430 extends CI_Migration
{
	private $tableName = 'le_gaming_game_logs';

    public function up() {

        $fields = array(
            'cur_score' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('cur_score', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cur_score', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cur_score');
            }
        }
    }
}