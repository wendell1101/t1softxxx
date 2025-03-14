<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_total_player_game_minute_additional_20210601 extends CI_Migration
{
	private $tableName = 'total_player_game_minute_additional';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('date_minute', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_date_minute','date_minute');
            }
            if($this->db->field_exists('game_platform_id', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_game_platform_id','game_platform_id');
            }
            if($this->db->field_exists('player_id', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            }
        }
    }

    public function down() {
    }
}