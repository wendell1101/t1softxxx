<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_jili_seamless_wallet_transactions_20211115 extends CI_Migration {
    
	private $tableName = 'jili_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'player_id' => array(
				'type' => 'BIGINT',
				'null' => false,
            ),
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
            'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
            'round' => array(
				'type' => 'BIGINT',
				'null' => true,
			), 
            'wagers_time' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			'wagers_time_parsed' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'bet_amount' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'winlose_amount' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'jp_contribute' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'jp_win' => array(
                'type' => 'double',
                'null' => true,
            ),   
            'is_free_round' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),    
            'user_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'preserve' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'turnover' => array(
                'type' => 'double',
                'null' => true,
            ),  
            'session_id' => array(
                'type' => 'BIGINT',
				'null' => true,
			),    
            'type' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ), 
            'game' => array(
				'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),  
			'reg_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),         

			# SBE additional info
            'trans_status' => array(
				'type' => 'INT',
				'constraint' => '10',
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
            //method+reqId
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
            $this->player_model->addIndex($this->tableName, 'idx_game', 'game');	        
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_round', 'round');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_wagers_time_parsed', 'wagers_time_parsed');	        
            $this->player_model->addIndex($this->tableName, 'idx_is_free_round', 'is_free_round');	                    
            $this->player_model->addIndex($this->tableName, 'idx_type', 'type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_user_id', 'user_id');	                    
            $this->player_model->addIndex($this->tableName, 'idx_session_id', 'session_id');
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
