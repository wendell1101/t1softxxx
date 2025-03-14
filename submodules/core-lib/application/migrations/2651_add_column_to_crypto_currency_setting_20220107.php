<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_crypto_currency_setting_20220107 extends CI_Migration {

	private $tableName = 'crypto_currency_setting';

	public function up() {
		$this->load->model(array('users'));
		$superAdmin = $this->users->getSuperAdmin();
        $this->db->where('crypto_currency', 'BNB');
        $this->db->delete($this->tableName);

		$this->db->insert_batch($this->tableName, array(
			array(
			'crypto_currency' 	=> 'BNB',
			'transaction' 		=> 'deposit',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'BNB',
			'transaction' 		=> 'withdrawal',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
		));
	}

	public function down() {

	}
}