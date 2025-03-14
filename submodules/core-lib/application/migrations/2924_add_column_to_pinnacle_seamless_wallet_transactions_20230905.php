<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pinnacle_seamless_wallet_transactions_20230905 extends CI_Migration {

	private $tableName = 'pinnacle_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'to_refund_transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
			),
		);


		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('to_refund_transaction_id', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('to_refund_transaction_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'to_refund_transaction_id');
            }
        }
	}
}