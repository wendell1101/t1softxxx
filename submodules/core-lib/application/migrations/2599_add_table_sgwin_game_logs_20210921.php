<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_sgwin_game_logs_20210921 extends CI_Migration {

	private $tableName = 'sgwin_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'betCode' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'betCount' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
            'betOdds' => array(
				'type' => 'DOUBLE',
                'null' => true
			),
            'bid' => array(
				'type' => 'INT',
				'constraint' => '20',
				'null' => true,
			),
            'channel' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'cm' => array(
                'type' => 'DOUBLE',
				'null' => true,
			),
            'content' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'betCreated' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'betDate' => array(
                'type' => 'DATE',
				'null' => true,
			),
            'dividend' => array(
                'type' => 'DOUBLE',
				'null' => true,
			),
            'gameCode' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'lottery' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'lotteryName' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'lotteryNumber' => array(
                'type' => 'INT',
				'constraint' => '20',
                'null' => true,
			),
            'odds' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
            'realAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
            'result' => array(
                'type' => 'INT',
                'constraint' => '5',
                'null' => true
			),
            'settled' => array(
                'type' => 'INT',
				'constraint' => '1',
                'null' => true,
			),
            'settledTime' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
            'status' => array(
                'type' => 'INT',
				'constraint' => '0',
                'null' => true,
			),
            'totalAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
		);

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_bet_created', 'betCreated');
            $this->player_model->addIndex($this->tableName, 'idx_settled_time', 'settledTime');
            $this->player_model->addIndex($this->tableName, 'idx_bet_date', 'betDate');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'gameCode');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}
