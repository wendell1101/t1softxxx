<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_agin_game_logs_20221117 extends CI_Migration
{
	private $tableName = 'agin_game_logs';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('updated_at', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
            }
        }
    }

    public function down() {
    }
}