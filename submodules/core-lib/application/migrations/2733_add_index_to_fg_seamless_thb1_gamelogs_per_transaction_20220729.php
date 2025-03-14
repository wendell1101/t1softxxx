<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_fg_seamless_thb1_gamelogs_per_transaction_20220729 extends CI_Migration
{
	private $tableName = 'fg_seamless_thb1_gamelogs_per_transaction';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('refund_tx_id', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_refund_tx_id','refund_tx_id');
            }
        }

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('round_id', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_round_id','round_id');
            }
        }
    }

    public function down() {
    }
}