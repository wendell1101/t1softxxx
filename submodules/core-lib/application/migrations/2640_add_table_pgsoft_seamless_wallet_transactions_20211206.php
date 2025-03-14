<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pgsoft_seamless_wallet_transactions_20211206 extends CI_Migration {
    
	private $tableName = 'pgsoft_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'operator_token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'secret_key' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'player_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'game_id' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),
            'bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),   
            'parent_bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),   
            'wallet_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),   
            'currency_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),   
            'bet_amount' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'win_amount' => array(
                'type' => 'double',
                'null' => true,
            ),   
            'transfer_amount' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),   
            'bet_type' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ), 
            'updated_time' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			), 
            'updated_time_parsed' => array(
                'type' => 'DATETIME',
                'null' => true,
			),   
            'adjustment_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),  
            'adjustment_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),  
            'adjustment_time' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			), 
            'adjustment_time_parsed' => array(
                'type' => 'DATETIME',
                'null' => true,
			),  
            'adjustment_transaction_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),  

            'operator_player_session' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'is_minus_count' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_validate_bet' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_adjustment' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_parent_zero_stake' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_feature' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_feature_buy' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'is_wager' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'free_game_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'free_game_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'free_game_id' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'bonus_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bonus_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bonus_id' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => true,
			),
            'bonus_balance_amount' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'bonus_ratio_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'jackpot_rtp_contribution_amount' => array(
                'type' => 'double',
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
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_parent_bet_id', 'parent_bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_updated_time_parsed', 'updated_time_parsed');	        
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
