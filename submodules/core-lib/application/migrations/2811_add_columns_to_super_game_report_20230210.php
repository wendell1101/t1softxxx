<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_super_game_report_20230210 extends CI_Migration
{
	private $tableName = 'super_game_report';


	public function up() {
		$field1 = array(
			'agent_id' => array(
				'type' => 'INT',
				'null' => true
			)
		);

		$field2 = array(
			'agent_username' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			)
		);

		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('agent_id', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $field1);
			}

			if(!$this->db->field_exists('agent_username', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $field2);
			}
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
			if($this->db->field_exists('agent_id', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'agent_id');
			}

			if($this->db->field_exists('agent_username', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'agent_username');
			}
		}
	}
}
