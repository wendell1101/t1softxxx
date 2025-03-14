<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_account_20200323 extends CI_Migration {

	private $tableName = 'payment_account';

	public function up() {
		$fields = array(

			'daily_deposit_limit_count' => array(
				'type' => 'INT',
				'null' => true,
			),
			'daily_deposit_count' => array(
				'type' => 'INT',
				'null' => false,
				'default' => 0,
			),
		);

		if (!$this->db->field_exists('daily_deposit_limit_count', $this->tableName) && !$this->db->field_exists('daily_deposit_count', $this->tableName))
		{
			$this->dbforge->add_column($this->tableName, $fields);
		}

	}

	public function down() {
		if (!$this->db->field_exists('daily_deposit_limit_count', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, $dailyDeposit);
		}
		if (!$this->db->field_exists('daily_deposit_count', $this->tableName)) {
			$this->dbforge->drop_column($this->tableName, $totalDeposit);
		}
	}
}

////END OF FILE////
