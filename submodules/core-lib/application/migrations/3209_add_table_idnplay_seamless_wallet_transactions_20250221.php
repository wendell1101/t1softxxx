<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_idnplay_seamless_wallet_transactions_20250221 extends CI_Migration {
	private $tableName = 'idnplay_seamless_wallet_transactions';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'player_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'operator_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'session_token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'uid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'round_id' => array(
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
            'game_group' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'provider_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'nickname' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'amount' => array(
				'type' => 'double',
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
            'bet_status' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'freespin' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet_details' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'json_details' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
            'ref_details' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
            'time' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),

            #additional
            'transaction_date' => array(
				'type' => 'DATETIME',
                'null' => true,
			),
			'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'extra_info' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
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
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_bet_type', 'bet_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');	        
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
