<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_player_activity_stream_20250205 extends CI_Migration {
	
	private $tableName = 'player_activity_stream';
	public function up() {
		$fields = array(
			'request_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' => false,
			),
			'player_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => false,
			),

		);
		$this->dbforge->modify_column($this->tableName, $fields);
	}

	public function down() {
	}
}
