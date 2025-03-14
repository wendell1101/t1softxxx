<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_betixon_seamless_wallet_transactions_20230907 extends CI_Migration {
    
	private $tableName = 'betixon_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'api_username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'api_password' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'debit_amount' => array(
                'type' => 'double',
                'null' => true,
            ), 
            'credit_amount' => array(
                'type' => 'double',
                'null' => true,
            ), 
            'result_amount' => array(
                'type' => 'double',
                'null' => true,
            ), 
            'game_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'rgs_player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'rgs_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'round_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'round_start' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'round_end' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'promo' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'code' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'is_done' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'total_spins' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'spins_done' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
            
            'total_balance' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'rgs_related_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'raw_data' => array(
				'type' => 'JSON',
				'null' => true,
			),

			# SBE additional info
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
            'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
            'trans_status' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),   
            'player_id' => array(
				'type' => 'BIGINT',
				'null' => false,
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
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');	 
            $this->player_model->addIndex($this->tableName, 'idx_rgs_player_id', 'rgs_player_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_rgs_transaction_id', 'rgs_transaction_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_total_balance', 'total_balance');	 
            $this->player_model->addIndex($this->tableName, 'idx_rgs_related_transaction_id', 'rgs_related_transaction_id');	                 
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	                
            $this->player_model->addIndex($this->tableName, 'idx_trans_status', 'trans_status');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');	        
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
