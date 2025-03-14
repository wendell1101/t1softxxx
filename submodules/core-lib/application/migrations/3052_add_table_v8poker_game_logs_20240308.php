<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_v8poker_game_logs_20240308 extends CI_Migration {

	private $tableName = 'v8poker_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'GameID' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
			),
            'Accounts' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
			),
            'ServerID' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'KindID' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'TableID' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'ChairID' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'UserCount' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'CardValue' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
			),
            'CellScore' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'AllBet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'Profit' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'Revenue' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'NewScore' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'GameStartTime' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'GameEndTime' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'ChannelID' => array(
                'type' => 'INT',
                'constraint' => '10',
				'null' => true,
			),
            'LineCode' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
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
            $this->player_model->addIndex($this->tableName, 'idx_GameID', 'GameID');
            $this->player_model->addIndex($this->tableName, 'idx_KindID', 'KindID');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
