<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pt_vnd_game_logs_20201106 extends CI_Migration {

	private $tableName = 'pt_vnd_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,
			),
			'playername' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
			),
			'gamename' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
			),
			'gameshortcode' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'gamecode' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'bet' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'win' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'gamedate' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'sessionid' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'gametype' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => false,
			),
			'windowcode' => array(
				'type' => 'INT',
				'null' => true,
			),
			'balance' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'progressivebet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'progressivewin' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'currentbet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'livenetwork' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gameid' => array(
				'type' => 'VARCHAR',
				'constraint' => '32',
				'null' => true,
			),
			'info' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
				'null' => true,
			),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => 200,
				'null' => false,
			),
			'response_result_id' => array(
				'type' => 'INT',
				'null' => true,
			)
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'idx_uniqueid', 'uniqueid');
	        $this->player_model->addIndex($this->tableName, 'idx_playername', 'playername');
	        $this->player_model->addIndex($this->tableName, 'idx_gamedate', 'gamedate');
	        $this->player_model->addIndex($this->tableName, 'idx_gameshortcode', 'gameshortcode');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}

