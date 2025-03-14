<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_genesis_seamless_transactions_20210301 extends CI_Migration
{
	private $tableName = 'genesis_seamless_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            
            if($this->db->field_exists('debitTxId', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_debitTxId','debitTxId');
            }
            if($this->db->field_exists('action', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_action','action');
            }
            if($this->db->field_exists('status', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_status','status');
            }            
        }
    }

    public function down() {
    }
}