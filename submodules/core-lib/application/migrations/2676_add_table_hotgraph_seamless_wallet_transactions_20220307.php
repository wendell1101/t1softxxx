<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_hotgraph_seamless_wallet_transactions_20220307 extends CI_Migration {
    
	private $tableName = 'hotgraph_seamless_wallet_transactions';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'betId' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'accountingDate' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'updateDate' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'stake' => array(  
                'type' => 'INT',
                'null' => true,
            ),
            'payout' => array( 
                'type' => 'DOUBLE',
                'null' => true
            ),
            'productId' => array( 
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'gameCode' => array( 
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'gameName' => array( 
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'roundId' => array( 
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'betStatus' => array( 
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'payoutStatus' => array( 
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
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'gameCode');
            $this->player_model->addIndex($this->tableName, 'idx_trans_type', 'trans_type');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'betId');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addIndex($this->tableName, 'idx_trans_status', 'trans_status');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
