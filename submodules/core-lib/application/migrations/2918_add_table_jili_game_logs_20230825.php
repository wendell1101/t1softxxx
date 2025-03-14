<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_jili_game_logs_20230825 extends CI_Migration {

	private $tableName = 'jili_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'transaction_id' => array( //WagersId 	
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
			'account' => array( //Account 	
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'game_category' => array( //GameCategoryId 
				'type' => 'INT',
				'null' => true,
			),
			'betting_date' => array( //WagersTime
                'type' => 'DATETIME',
                'null' => true,
			),
			'time_settled' => array( //SettlementTime
                'type' => 'DATETIME',
                'null' => true,
			),
			'bet_amount' => array( //betAmount
				'type' => 'double',
				'null' => true,
			),
			'payoff_time' => array( //PayoffTime
                'type' => 'DATETIME',
                'null' => true,
            ),
            'payoff_amount' => array(  //PayoffAmount
                'type' => 'double',
                'null' => true,
            ),
            'status' => array(  //Status
                'type' => 'INT',
                'null' => true,
			),
			'version_key' => array(  //VersionKey
                'type' => 'INT',
                'null' => true,
            ),
            'type' => array(  //Type
                'type' => 'INT',
                'null' => true,
			),
			'agent_id' => array(  //AgentId
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'turnover' => array(  //TurnOver
				'type' => 'double',
				'null' => true,
			),
			'result_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
			'game_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_account', 'account');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
        
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
