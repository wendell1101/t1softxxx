<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_endorphina_seamless_wallet_transactions_20240224 extends CI_Migration {
    
	private $tableName = 'endorphina_seamless_wallet_transactions';
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
			'amount' => array(
				'type' => 'double',
                'null' => true,
			),
			'timestamp' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'datetime' => array(
				'type' => 'DATETIME',
                'null' => true,
			),
			'game' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'game_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'player' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'sign' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_session_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'progressive' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'progressive_desc' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'win_description' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'promo_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'promo_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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
			'extra_info' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
            'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),

			# SBE additional info
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
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
