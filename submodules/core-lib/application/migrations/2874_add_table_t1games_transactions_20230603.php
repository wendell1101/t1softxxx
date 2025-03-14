<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_t1games_transactions_20230603 extends CI_Migration {
    
	private $tableName = 't1games_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'timestamp' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'timestamp_parsed' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'merchant_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'amount' => array(
                'type' => 'double',
                'null' => true,
            ),
            'game_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ),
            'number' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'opencode' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
            'round_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),            
            'bet_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
            'player_id' => array(
				'type' => 'BIGINT',
				'null' => false,
            ),
            'trans_type' => array(
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
			'game_platform_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => false,
			),	
            'status' => array(
                'type' => 'TINYINT',
                'null' => true,
                'default' => 0,
			),
			'raw_data' => array(
                'type' => 'TEXT',
                'null' => true
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
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');	                    
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');	                    
            $this->player_model->addIndex($this->tableName, 'idx_timestamp_parsed', 'timestamp_parsed');	        
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	        
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');	        
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
