<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_hydako_game_logs_20200520 extends CI_Migration {

	private $tableName = 'hydako_thb1_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'seq_id' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			'user_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true
			),
			'gameName' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'slotType' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'status' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'win' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'jackpot' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
			'regDate' => array(
				'type' => 'VARCHAR',
				'constraint' => '60',
                'null' => true,
			),
			'ip' => array(
				'type' => 'VARCHAR',
				'constraint' => '60',
                'null' => true,
			),
			'freeWin' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'startCash' => array(
				'type' => 'DOUBLE',
                'null' => true,
			),
			'endCash' => array(
                'type' => 'DOUBLE',
				'null' => true,
			),
			'coin' => array(
                'type' => 'INT',
                'constraint' => '16',
				'null' => true,
			),

			
			# SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');
	        $this->player_model->addIndex($this->tableName, 'idx_regDate', 'regDate');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_seq_id', 'seq_id');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
