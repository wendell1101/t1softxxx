<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_sexy_baccarat_transactions_20210216 extends CI_Migration
{
	private $tableName = 'sexy_baccarat_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('platformTxId', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_platformTxId','platformTxId');
            }
        }
    }

    public function down() {
    }
}