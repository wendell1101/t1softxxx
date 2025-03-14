<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_vivogaming_transactions_idr1_20200818 extends CI_Migration {

	private $tableName = 'vivogaming_transactions_idr1';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),		
            'trans_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'trans_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),	
            'amount' => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'before_balance' => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'after_balance' => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'trans_desc' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'round_id' => array(
				'type' => 'BIGINT',
				'null' => true,
			),	
            'game_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
			),
            'history' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'is_round_finished' => array(
                "type" => "TINYINT",
                "null" => true
			),
            'hash' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'session_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ),
            'player_id' => array(
				'type' => 'BIGINT',
				'null' => true,
            ),       
            'trans_time' => array(
                "type" => "DATETIME",
                "null" => true
            ),
            'status' => array(
                "type" => "TINYINT",
                "null" => true
            ),
            'response_result_id' => array(
				'type' => 'BIGINT',
				'null' => true,
            ),  	            
            'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
            ),
            'raw_data' => array(
				'type' => 'VARCHAR',
				'constraint' => '250',
				'null' => true,
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
