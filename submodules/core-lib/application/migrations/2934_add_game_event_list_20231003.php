<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_game_event_list_20231003 extends CI_Migration {

	private $tableName = 'game_event_list';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
			'game_platform_id' => array(
				'type' => 'INT',
				'null' => false,
            ),
            'league_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
			),
            'event_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
			),
			'extra' => array(
				'type' => 'JSON',
				'null' => true,
			),
			'status' => array(
				'type' => 'TINYINT',
				'null' => false,
			),
			'created_at' => array(
				'type' => 'DATETIME',
                'null' => false
            ),
		);

		if(!$this->utils->table_really_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);

			$this->load->model(['player_model']);
			$this->player_model->addIndex($this->tableName, 'idx_event_id', 'event_id');
			$this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
