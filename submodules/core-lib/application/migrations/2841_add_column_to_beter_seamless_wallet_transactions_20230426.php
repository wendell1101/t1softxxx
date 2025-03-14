<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_beter_seamless_wallet_transactions_20230426 extends CI_Migration {

	private $tableName = 'beter_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'total_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
		);
		

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('total_bet_amount', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('total_bet_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'total_bet_amount');
            }
        }
	}
}