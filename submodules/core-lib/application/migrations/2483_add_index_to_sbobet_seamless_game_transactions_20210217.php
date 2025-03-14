<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_sbobet_seamless_game_transactions_20210217 extends CI_Migration
{
	private $tableName = 'sbobet_seamless_game_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transaction_type', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_transfer_code_transaction_type','transfer_code,transaction_type');
            }
        }
    }

    public function down() {
    }
}