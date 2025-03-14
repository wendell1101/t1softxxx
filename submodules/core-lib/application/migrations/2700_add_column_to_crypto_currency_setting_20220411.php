<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_crypto_currency_setting_20220411 extends CI_Migration {

	private $tableName = 'crypto_currency_setting';

	public function up() {
		$this->load->model(array('users'));
		$superAdminId = $this->users->getSuperAdminId();
        $this->db->where('crypto_currency', 'USDTL');
        $this->db->delete($this->tableName);

		$this->db->insert_batch($this->tableName, array(
			array(
			'crypto_currency' 	=> 'USDTL',
			'transaction' 		=> 'deposit',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdminId
			),
			array(
			'crypto_currency' 	=> 'USDTL',
			'transaction' 		=> 'withdrawal',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdminId
			),
		));
	}

	public function down() {

	}
}