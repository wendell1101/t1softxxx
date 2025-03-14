<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_game_logs_stream_20240717 extends CI_Migration {

    

	private $tableName = 'game_logs_stream';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('game_platform_id', $this->tableName)){
                $this->player_model->addUniqueIndex($this->tableName,'idx_game_logs_stream_external_uniqueid','game_platform_id, external_uniqueid');
            }
        }
    }

    public function down() {
    }
}
