<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_kingpoker_gamelogs_20200529 extends CI_Migration {

	private $origTableName = 'kingpoker_gamelogs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_on' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			),
			'round_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			),
			'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'ip' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'valid_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'start_bet_time' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'stop_bet_time' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'payout_time' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'payout' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'surplus' => array(
                'type' => 'DOUBLE',
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
				'constraint' => '100',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            )
		);

	    if(!$this->db->table_exists($this->origTableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->origTableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->origTableName, 'idx_type', 'type');
	        $this->player_model->addIndex($this->origTableName, 'idx_round_id', 'round_id');
	        $this->player_model->addIndex($this->origTableName, 'idx_username', 'username');
	        $this->player_model->addIndex($this->origTableName, 'idx_md5_sum', 'md5_sum');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_bet_id', 'bet_id');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->origTableName);
	}
}
