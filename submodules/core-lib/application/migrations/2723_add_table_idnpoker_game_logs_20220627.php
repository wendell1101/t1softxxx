<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_idnpoker_game_logs_20220627 extends CI_Migration {

	private $tableName = 'idnpoker_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),

            'bet_date' => array(
				'type' => 'DATETIME',
                'null' => false,
			),
            'player' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => false,
			),
            'periode' => array(
                'type' => 'INT',
				'null' => true,
			),
            'table_type' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'card' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'prize' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'game_status' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
			),
            'winlose' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
			),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'table_fee' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'total_coin' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'game_code' => array(
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
            $this->player_model->addIndex($this->tableName, 'idx_bet_date', 'bet_date');
            $this->player_model->addIndex($this->tableName, 'idx_player', 'player');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
