<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_creedroomz_seamless_wallet_transactions_20240429 extends CI_Migration {
	private $tableName = 'creedroomz_seamless_wallet_transactions';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'operator_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'withdraw_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'deposit_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'game_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'rgs_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'rgs_related_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'type_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'bonus_def_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'public_key' => array(
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
			
			# SBE additional info
            'currency' => array(
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
            $this->player_model->addIndex($this->tableName, 'idx_rgs_transaction_id', 'rgs_transaction_id');	
            $this->player_model->addIndex($this->tableName, 'idx_rgs_related_transaction_id', 'rgs_related_transaction_id');	
            $this->player_model->addIndex($this->tableName, 'idx_operator_id', 'operator_id');	                    
            $this->player_model->addIndex($this->tableName, 'idx_withdraw_amount', 'withdraw_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_deposit_amount', 'deposit_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_amount', 'amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_bet_amount', 'bet_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_payout_amount', 'payout_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_result_amount', 'result_amount');	                    
            $this->player_model->addIndex($this->tableName, 'idx_type_id', 'type_id');	                    
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');	        
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
