<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_simpleplay_game_logs_20201117 extends CI_Migration {

	private $tableName = 'simpleplay_game_logs';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'bet_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'payout_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
                'null' => true,
			),
			'host_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
                'null' => true,
			),
			'game_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'round' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
                'null' => true,
			),
			'set' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
                'null' => true,
			),
			'bet_id' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			'bet_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'rolling' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'result_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'after_balance' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'game_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'bet_source' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'detail' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'transaction_id' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			'game_externalid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'md5_sum' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'response_result_id' => array(
				'type' => 'int',
				'null' => true,
			),
			"created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            )
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');
	        $this->player_model->addIndex($this->tableName, 'idx_payout_time', 'payout_time');
	        $this->player_model->addIndex($this->tableName, 'idx_payout_time', 'payout_time');
	        $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
	        $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
	        $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
