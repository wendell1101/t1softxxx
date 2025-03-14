<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_common_seamless_wallet_transactions_20200411 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        $fields = array(
            'transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('transaction_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);            
            $this->player_model->addIndex($this->tableName,"idx_transaction_id","transaction_id");
        }
    }

    public function down() {
        if($this->db->field_exists('transaction_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'transaction_id');
        }
    }
}