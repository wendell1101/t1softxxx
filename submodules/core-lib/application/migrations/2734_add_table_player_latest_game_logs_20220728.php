<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_latest_game_logs_20220728 extends CI_Migration {

	private $tableName = 'player_latest_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'player_id' => array(
				'type' => 'INT',
				'null' => false,
			),
			'bet_at' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'end_at' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'bet_amount' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'win_amount' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'loss_amount' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'odds' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
			'game_platform_id' => array(
				'type' => 'INT',
				'null' => false,
			),
			'game_type_id' => array(
				'type' => 'INT',
				'null' => false,
			),
			'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
		);

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_bet_at', 'bet_at');
			$this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
			$this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
	}

	public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}

