<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_game_type_20230111 extends CI_Migration
{
	private $tableName = 'game_type';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('game_type_code', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_game_type_code','game_type_code');
            }
        }
    }

    public function down() {
    }
}