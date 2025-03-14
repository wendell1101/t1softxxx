<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_ab_game_logs_20241210 extends CI_Migration
{
	private $tableName = 'ab_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('gameRoundEndTime', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_gameRoundEndTime','gameRoundEndTime');
            }

            if($this->db->field_exists('gameRoundStartTime', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_gameRoundStartTime','gameRoundStartTime');
            }

            if($this->db->field_exists('updatedAt', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_updatedAt','updatedAt');
            }
        }
    }

    public function down() {
    }
}