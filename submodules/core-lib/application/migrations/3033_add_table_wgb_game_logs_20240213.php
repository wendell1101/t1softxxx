<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_wgb_game_logs_20240213 extends CI_Migration {

	private $tableName = 'wgb_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'player_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
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
            'round_id' => array( 
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'bet_date' => array( 
                'type' => 'DATETIME',
                'null' => true,
			),
            'bet_amount' => array( 
                'type' => 'double',
                'null' => true,
			),
            'odds' => array( 
                'type' => 'double',
                'null' => true,
			),
            'payout' => array( 
                'type' => 'double',
                'null' => true,
			),
            'refund' => array( 
                'type' => 'double',
                'null' => true,
			),
			'bet_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),	
            'winner' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),	
            'status' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),	
            'game_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),		
            'extra_info' => array(
				'type' => 'JSON',
                'null' => TRUE,
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
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );
        
        if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            
			# Add Index
	        $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_name', 'player_name');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
        
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
