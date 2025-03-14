<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_game_event_list_20231006 extends CI_Migration
{
	private $tableName = 'game_event_list';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('game_platform_id', $this->tableName)){
                $this->player_model->addUniqueIndex($this->tableName,'idx_game_platform_id_event_id','game_platform_id, event_id');
            }
        }
    }

    public function down() {
    }
}