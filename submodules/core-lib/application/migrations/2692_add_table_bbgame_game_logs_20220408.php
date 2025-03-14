<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_bbgame_game_logs_20220408 extends CI_Migration {

	private $tableName = 'bbgame_game_logs';

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
            'company' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'roundId' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'account' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'orderCreateTime' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'gameType' => array(
                'type' => 'INT',
				'constraint' => '20',
                'null' => true,
			),
            'revenue' => array(
                'type' => 'INT',
				'constraint' => '20',
                'null' => true,
			),
            'actionType' => array(
                'type' => 'INT',
				'constraint' => '20',
                'null' => true,
			),
            'betFatAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'initBetAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'beforeAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'afterAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'tax' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'code' => array(
                'type' => 'INT',
				'constraint' => '20',
                'null' => true,
			),
            'desc' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'validAmount' => array(
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
            $this->player_model->addIndex($this->tableName, 'idx_order_create_time', 'orderCreateTime');
            $this->player_model->addIndex($this->tableName, 'idx_account', 'account');
            $this->player_model->addIndex($this->tableName, 'idx_game_type', 'gameType');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
