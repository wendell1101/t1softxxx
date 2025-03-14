<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pgsoft_seamless_wallet_transactions_20230425_2 extends CI_Migration {

	private $tableName = 'pgsoft_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'orig_bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
			'orig_win_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
			'orig_transfer_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
		);


		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('orig_bet_amount', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('orig_bet_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'orig_bet_amount');
            }
        }
	}
}