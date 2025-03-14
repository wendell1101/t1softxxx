<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_mgplus_seamless_wallet_transactions_20250314 extends CI_Migration {
	private $tableNames = [
		'mgplus_seamless_wallet_transactions',
		'mgplus_seamless_wallet_transactions_202503',
		'idn_slots_mgplus_seamless_wallet_transactions',
		'idn_slots_mgplus_seamless_wallet_transactions_202503',
		'idn_live_mgplus_seamless_wallet_transactions',
		'idn_live_mgplus_seamless_wallet_transactions_202503',
	];
	public function up() {
		// GP max is 256, update to 300 to avoid being truncated
		$fields = array(
			'transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => false,
			),
			'external_unique_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => false,
			),
		);
		foreach ($this->tableNames as $tableName){
			if($this->db->table_exists($tableName)){
				$this->CI->load->model(['player_model']);
				$this->dbforge->modify_column($tableName, $fields);
			}
		}

	}

	public function down() {
	}
}
