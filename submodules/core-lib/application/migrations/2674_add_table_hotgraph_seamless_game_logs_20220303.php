<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_hotgraph_seamless_game_logs_20220303 extends CI_Migration {

	private $tableName = 'hotgraph_seamless_game_logs';

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
           'response_result_id' => array(  //record response_results.id
                'type' => 'INT',
                'null' => true,
            ),
            'external_uniqueid' => array(   //unique id of each row from api
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at' => array(  // first create date time
                'type' => 'DATETIME',
                'null' => false,
            ),
            'updated_at' => array(  // last update time
                'type' => 'DATETIME',
                'null' => false,
            ),
            'md5_sum' => array(  
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),

		);

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
            $this->player_model->addIndex($this->tableName, 'idx_accountingDate', 'accountingDate');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_gameCode', 'gameCode');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
