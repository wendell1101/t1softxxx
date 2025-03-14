<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_im_seamless_wallet_transactions_20231009 extends CI_Migration {
    
	private $tableName = 'im_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
           
            'product_wallet' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'session_token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'im_player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'provider_player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'provider' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'game_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'game_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'action_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => true,
			),
            'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'game_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'amount' => array(
                'type' => 'double',
                'null' => true,
            ), 
			'timestamp' => array(
                'type' => 'DATETIME',
                'null' => true,
            ), 
			'bet_date' => array(
                'type' => 'DATETIME',
                'null' => true,
            ), 
			'product' => array(
                'type' => 'INT',
				'constraint' => 11,
                'null' => true,
            ), 
			'platform' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'event_id' => array(
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
            $this->player_model->addIndex($this->tableName, 'idx_product_wallet', 'product_wallet');	 
            $this->player_model->addIndex($this->tableName, 'idx_session_token', 'session_token');	 
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_game_name', 'game_name');	 
            $this->player_model->addIndex($this->tableName, 'idx_im_player_id', 'im_player_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_provider_player_id', 'provider_player_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_action_id', 'action_id');	 
            $this->player_model->addIndex($this->tableName, 'idx_type', 'type');	 
            $this->player_model->addIndex($this->tableName, 'idx_game_type', 'game_type');	 
            $this->player_model->addIndex($this->tableName, 'idx_currency', 'currency');	 
            $this->player_model->addIndex($this->tableName, 'idx_amount', 'amount');	 
            $this->player_model->addIndex($this->tableName, 'idx_bet_date', 'bet_date');	 
            $this->player_model->addIndex($this->tableName, 'idx_product', 'product');	 
                             
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
