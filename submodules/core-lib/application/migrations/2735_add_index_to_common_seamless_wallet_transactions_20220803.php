<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_common_seamless_wallet_transactions_20220803 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('start_at', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_start_at','start_at');
            }
        }
    }

    public function down() {
    }
}