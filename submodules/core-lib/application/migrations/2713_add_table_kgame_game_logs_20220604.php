<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_kgame_game_logs_20220604 extends CI_Migration {

	private $tableName = 'kgame_game_logs';

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
            'channel' => array(
                'type' => 'INT',
                'null' => true,
			),
            'bet_time' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'game_type' => array(
                'type' => 'INT',
                'constraint' => '1',
                'null' => true,
			),
            'game_id' => array(
                'type' => 'INT',
                'null' => true,
			),
            'game_code' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'tableno' => array(
                'type' => 'INT',
                'null' => true,
			),
            'termno' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet_item_name' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'bet_money' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'bbef_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'lw_money' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'tax' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'tax' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'valid_bet' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'send_prize' => array(
                'type' => 'DOUBLE',
                'null' => true
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
            $this->player_model->addIndex($this->tableName, 'idx_bet_time', 'bet_time');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
