<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_levelName_groupName_in_player_20210915 extends CI_Migration {

	private $tableName = 'player';

	public function up() {

		$fields = array(
			'levelName' => array(
				'name' => 'levelName',
				'type' => 'VARCHAR',
				'constraint' => '1024',
				'null' => true,
			),
			'groupName' => array(
				'name' => 'groupName',
				'type' => 'VARCHAR',
				'constraint' => '1024',
				'null' => true,
			),
		);
		$this->dbforge->modify_column($this->tableName, $fields);

	}

	public function down() {
		$fields = array(
			'levelName' => array(
				'name' => 'levelName',
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'groupName' => array(
				'name' => 'groupName',
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
		);
		$this->dbforge->modify_column($this->tableName, $fields);
	}



}