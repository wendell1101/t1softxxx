<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_imesb_game_logs_20211109 extends CI_Migration {

	private $tableName = 'imesb_game_logs';

	public function up() {
		//modify column
		$fields = array(
			'sportsid' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			)
		);
		$this->dbforge->modify_column($this->tableName, $fields);
	}

	public function down() {
	}
}