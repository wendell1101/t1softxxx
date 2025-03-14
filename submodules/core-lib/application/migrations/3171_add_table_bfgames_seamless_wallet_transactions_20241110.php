<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_bfgames_seamless_wallet_transactions_20241110 extends CI_Migration {
	private $tableName = 'bfgames_seamless_wallet_transactions';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
			'caller_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'caller_password' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'methodname' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'mirror_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'game_ref' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'game_ver' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'round_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'action_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bonus_program_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bonus_instance_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bonus_system_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'round_end' => array(
				'type' => 'INT',
				'default' => 0
			),
			'raw_data' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
			'round_details' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
			'jackpot_winnings' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
			'jackpot_contributions' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
			'amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'bet_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'payout_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'valid_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'result_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'player_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'game_username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'raw_data' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
			'remote_wallet_status' => array(
                'type' => 'INT',
                'null' => true,
            ),
			'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
            'headers' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'full_url' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
			'remote_raw_data' => array(
                'type' => 'JSON',
                'null' => true,
            ),


			# SBE additional info
			'win_amount' => array(
				'type' => 'double',
                'null' => true,
			),
            'status' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),   
			'balance_adjustment_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'balance_adjustment_method' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			), 
            'before_balance' => array(
                'type' => 'double',
                'null' => true,
            ), 
            'after_balance' => array(
                'type' => 'double',
                'null' => true,
            ), 
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),            
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
				'unique' => true 
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'elapsed_time' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_ref', 'game_ref');
			$this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
			$this->player_model->addIndex($this->tableName, 'idx_action_id', 'action_id');
			$this->player_model->addIndex($this->tableName, 'idx_methodname', 'methodname');
			$this->player_model->addIndex($this->tableName, 'idx_mirror_id', 'mirror_id');
			$this->player_model->addIndex($this->tableName, 'idx_bonus_program_id', 'bonus_program_id');
			$this->player_model->addIndex($this->tableName, 'idx_bonus_instance_id', 'bonus_instance_id');
			$this->player_model->addIndex($this->tableName, 'idx_bonus_system_id', 'bonus_system_id');
			$this->player_model->addIndex($this->tableName, 'idx_bet_amount', 'bet_amount');
			$this->player_model->addIndex($this->tableName, 'idx_payout_amount', 'payout_amount');
			$this->player_model->addIndex($this->tableName, 'idx_valid_amount', 'valid_amount');
			$this->player_model->addIndex($this->tableName, 'idx_result_amount', 'result_amount');
			$this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
			$this->player_model->addIndex($this->tableName, 'idx_game_username', 'game_username');
			$this->player_model->addIndex($this->tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
			$this->player_model->addIndex($this->tableName, 'idx_request_id', 'request_id');
			$this->player_model->addIndex($this->tableName, 'idx_full_url', 'full_url');
			$this->player_model->addIndex($this->tableName, 'idx_win_amount', 'win_amount');
			$this->player_model->addIndex($this->tableName, 'idx_status', 'status');
			$this->player_model->addIndex($this->tableName, 'idx_balance_adjustment_method', 'balance_adjustment_method');
			$this->player_model->addIndex($this->tableName, 'idx_before_balance', 'before_balance');
			$this->player_model->addIndex($this->tableName, 'idx_after_balance', 'after_balance');
			$this->player_model->addIndex($this->tableName, 'idx_response_result_id', 'response_result_id');
			$this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
			$this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
			$this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
			$this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
