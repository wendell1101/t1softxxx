<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_total_player_game_day_timezone_20201110 extends CI_Migration {

	private $tableName = 'total_player_game_day_timezone';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'player_id' => array(
				'type' => 'INT',
				'null' => true,
			),
			'betting_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'date' => array(
				'type' => 'DATE',
				'null' => true,
			),
			'game_description_id' => array(
				'type' => 'INT',
				'null' => true,
			),
			'game_platform_id' => array(
				'type' => 'INT',
				'null' => true,
			),
			'game_type_id' => array(
				'type' => 'INT',
				'null' => true,
			),
			'uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'result_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'win_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'loss_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'real_betting_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'bet_for_cashback' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'update_date_day' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
                'null' => true,
			),
			'md5_sum' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'currency_key' => array(
				'type' => 'VARCHAR',
				'constraint' => '5',
                'null' => true,
			),
			'timezone' => array(
				'type' => 'INT',
                'null' => true,
			),
			"created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            )
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
	        $this->player_model->addIndex($this->tableName, 'idx_date', 'date');
	        $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
	        $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_uniqueid', 'uniqueid');
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
