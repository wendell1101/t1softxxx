<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_common_seamless_wallet_transactions_20210203 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_platform_id', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_commonseamlesswallettransaction_game_platform_id','game_platform_id');
            }
        }
    }

    public function down() {
    }
}