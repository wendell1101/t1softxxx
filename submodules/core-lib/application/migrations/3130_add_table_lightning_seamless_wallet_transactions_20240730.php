<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_lightning_seamless_wallet_transactions_20240730 extends CI_Migration {
	private $tableName = 'lightning_seamless_wallet_transactions';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
			'transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'timestamp' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'agent_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'total_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bets' => array(
					'type' => 'JSON',
					'null' => TRUE,
			),
            'room_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'issue' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'gameType' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
            'playType' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'chipTypes' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'odds' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'betTime' => array(
				'type' => 'DATETIME',
				'null' => true
			),
			'updateTime' => array(
				'type' => 'DATETIME',
				'null' => true
			),
			'game_status' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'game_payout' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'prize' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'poker' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
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
			'bet_amount' => array(
				'type' => 'double',
                'null' => true,
			),
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
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');	
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');	
            $this->player_model->addIndex($this->tableName, 'idx_timestamp', 'timestamp');	
            $this->player_model->addIndex($this->tableName, 'idx_token', 'token');	
            $this->player_model->addIndex($this->tableName, 'idx_agent_name', 'agent_name');	
            $this->player_model->addIndex($this->tableName, 'idx_room_id', 'room_id');	
            $this->player_model->addIndex($this->tableName, 'idx_issue', 'issue');	
            $this->player_model->addIndex($this->tableName, 'idx_gameType', 'gameType');	
            $this->player_model->addIndex($this->tableName, 'idx_bet', 'bet');	                    
            $this->player_model->addIndex($this->tableName, 'idx_amount', 'amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_bet_amount', 'bet_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_payout_amount', 'payout_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_result_amount', 'result_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_odds', 'odds');	                    
            $this->player_model->addIndex($this->tableName, 'idx_betTime', 'betTime');	        
            $this->player_model->addIndex($this->tableName, 'idx_updateTime', 'updateTime');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_status', 'game_status');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_payout', 'game_payout');	      
            $this->player_model->addIndex($this->tableName, 'idx_prize', 'prize');	
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');	         
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');	        
			$this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
