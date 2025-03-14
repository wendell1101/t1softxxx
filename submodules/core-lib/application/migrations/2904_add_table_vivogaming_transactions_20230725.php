<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_vivogaming_transactions_20230725 extends CI_Migration {

	private $tableName = 'vivogaming_transactions';

	public function up() {
		$fields = [
			'id' => [
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ],		
            'trans_id' => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ],
            'trans_type' => [
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			],	
            'amount' => [
                "type" => "DOUBLE",
                "null" => true
            ],
            'before_balance' => [
                "type" => "DOUBLE",
                "null" => true
            ],
            'after_balance' => [
                "type" => "DOUBLE",
                "null" => true
            ],
            'trans_desc' => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ],
            'round_id' => [
				'type' => 'BIGINT',
				'null' => true,
			],	
            'game_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
			],
            'history' => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ],
            'is_round_finished' => [
                "type" => "TINYINT",
                "null" => true
			],
            'hash' => [
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ],
            'session_id' => [
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ],
            'player_id' => [
				'type' => 'BIGINT',
				'null' => true,
            ],       
            'trans_time' => [
                "type" => "DATETIME",
                "null" => true
            ],
            'status' => [
                "type" => "TINYINT",
                "null" => true
            ],            
            'currency' => [
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
            ],
            'raw_data' => [
				'type' => 'VARCHAR',
				'constraint' => '250',
				'null' => true,
            ],
            'seamless_service_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'external_game_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],

			# SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'external_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
		];

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            
			# Add Index
	        $this->load->model('player_model');	        
	                    
            $this->player_model->addIndex($this->tableName, 'idx_trans_id', 'trans_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');	        
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');	
            $this->player_model->addIndex($this->tableName, 'idx_trans_time', 'trans_time');	            
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
