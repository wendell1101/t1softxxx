<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_modify_column_cur_score_to_le_gaming_game_logs_20201219 extends CI_Migration
{
	private $tableName = 'le_gaming_game_logs';

    public function up() {

        $column = array(
            'cur_score' => array(
                'name' => 'CurScore',
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cur_score', $this->tableName)){
                $this->dbforge->modify_column($this->tableName,$column);
            }
        }
    }

    public function down() {

    }
}