<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_adjust_seamless_single_wallet_20200602 extends CI_Migration {

    private $tableName = 'seamless_single_wallet';

    public function up() {
        $this->load->model('player_model');
        if($this->db->field_exists('game_platform_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'game_platform_id');
        }
        $this->player_model->dropIndex($this->tableName, 'idx_player_id');
        $this->player_model->addUniqueIndex($this->tableName, 'idx_player_id', 'player_id');
    }

    public function down() {
    }
}