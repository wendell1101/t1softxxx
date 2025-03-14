<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_common_seamless_wallet_transactions_20240603 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('status', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_status','status');
            }
            if($this->db->field_exists('transaction_type', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_transaction_type','transaction_type');
            }
            if($this->db->field_exists('created_at', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_created_at','created_at');
            }
        }
    }

    public function down() {
    }
}