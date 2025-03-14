<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_game_logs_stream20240710 extends CI_Migration
{
	private $tableName = 'game_logs_stream';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_logs_stream');
            if($this->db->field_exists('start_at', $this->tableName)){                
                $this->game_logs_stream->addIndex($this->tableName,'idx_start_at','start_at');
            }
            if($this->db->field_exists('end_at', $this->tableName)){                
                $this->game_logs_stream->addIndex($this->tableName,'idx_end_at','end_at');
            }
        }
    }

    public function down() {
    }
}