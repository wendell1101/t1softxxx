<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_sexy_baccarat_game_logs_20200330 extends CI_Migration {

	private $tableName = 'sexy_baccarat_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			"platformTxId" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "userId" => array(
                "type" => "VARCHAR",
                "constraint" => "25",
                "null" => true
            ),
            "currency" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "platform" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "gameType" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "gameCode" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "gameName" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "betType" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "betAmount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "winAmount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "betTime" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "roundId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "gameInfo" => array(
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true
            ),
            "before_balance" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "after_balance" => array(
                "type" => "DOUBLE",
                "null" => true
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
	        $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_userId","userId");
            $this->player_model->addIndex($this->tableName,"idx_roundId","roundId");
            $this->player_model->addIndex($this->tableName,"idx_betTime","betTime");
            $this->player_model->addUniqueIndex($this->tableName,"idx_platformTxId","platformTxId");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
		}
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
