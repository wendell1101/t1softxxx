<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_crypto_currency_setting_20210728 extends CI_Migration {

	private $tableName = 'crypto_currency_setting';

	public function up() {
		$this->load->model(array('users'));

		$superAdmin = $this->users->getSuperAdmin();

		$this->db->insert_batch($this->tableName, array(
			array(
			'crypto_currency' 	=> 'ETH',
			'transaction' 		=> 'deposit',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'ETH',
			'transaction' 		=> 'withdrawal',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'BTC',
			'transaction' 		=> 'deposit',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'BTC',
			'transaction' 		=> 'withdrawal',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'USDT',
			'transaction' 		=> 'deposit',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			),
			array(
			'crypto_currency' 	=> 'USDT',
			'transaction' 		=> 'withdrawal',
			'exchange_rate_multiplier' 	=> 1,
			'created_at' => $this->utils->getNowForMysql(),
			'update_at' => $this->utils->getNowForMysql(),
			'update_by' => $superAdmin->userId
			)
		));
	}

	public function down() {
		$this->db->delete($this->tableName);
	}
}