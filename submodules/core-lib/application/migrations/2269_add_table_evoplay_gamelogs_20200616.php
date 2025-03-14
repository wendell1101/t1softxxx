<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_evoplay_gamelogs_20200616 extends CI_Migration {

	private $origTableName = 'evoplay_gamelogs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'pay_for_action_this_round' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'lines' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
			),
			'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'balance_before_pay' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'balance_after_pay' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'game' => array(
                'type' => 'json',
                'null' => true,
			),
			'game_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'round' => array(
                'type' => 'json',
                'null' => true,
			),
			'round_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'total_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'total_win' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'denomination' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'user' => array(
                'type' => 'json',
                'null' => true,
			),
			'user_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'currency_rate' => array(
                'type' => 'json',
                'null' => true,
			),
			'event_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'time' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'date' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'type_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'system_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'system_key' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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
	        $this->player_model->addIndex($this->origTableName, 'idx_round_id', 'round_id');
	        $this->player_model->addIndex($this->origTableName, 'idx_game_id', 'game_id');
	        $this->player_model->addIndex($this->origTableName, 'idx_user_id', 'user_id');
	        $this->player_model->addIndex($this->origTableName, 'idx_md5_sum', 'md5_sum');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_event_id', 'event_id');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->origTableName);
	}
}
