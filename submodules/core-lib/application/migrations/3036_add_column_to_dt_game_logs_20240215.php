<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_dt_game_logs_20240215 extends CI_Migration {

	private $tableName = 'dt_game_logs';

	public function up() {
		$fields = array(
			'fcid' => array(
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => true,
			),
		);

		$this->dbforge->add_column($this->tableName, $fields);
	}

	public function down() {
		$this->dbforge->drop_column($this->tableName, 'fcid');
	}
}

////END OF FILE////