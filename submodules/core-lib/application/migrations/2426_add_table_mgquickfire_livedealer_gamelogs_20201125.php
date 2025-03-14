<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_mgquickfire_livedealer_gamelogs_20201125 extends CI_Migration {

	private $tableName = 'mgquickfire_livedealer_gamelogs';
	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'placeBetTime' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'tableCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
                'null' => true,
			),
			'roundId' => array(
				'type' => 'INT',
				'null' => true,
			),
			'betPool' => array(
				'type' => 'INT',
				'null' => true,
			),
			'currencyIsoCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
                'null' => true,
			),
			'ticketStatusId' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
                'null' => true,
			),
			'ticketStatusId' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
                'null' => true,
			),
			'completed' => array(
                'type' => 'boolean',
                'null' => true,
            ),
            'betAmount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'gainAmount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'ipAddress' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
                'null' => true,
			),
			'userName' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
                'null' => true,
			),
			'playerMode' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
                'null' => true,
			),
			'userTransNumber' => array(
				'type' => 'INT',
				'null' => true,
			),
			'betDetails' => array(
                'type' => 'json',
                'null' => true,
			),
			'amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'description' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'actionStatusID' => array(
				'type' => 'INT',
				'null' => true,
			),
			'externalBalanceActionID' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			#default
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
			),
			'md5_sum' => array(
				'type' => 'VARCHAR',
				'constraint' => '64',
                'null' => true,
			),
			'response_result_id' => array(
				'type' => 'int',
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
	        $this->player_model->addIndex($this->tableName, 'idx_placeBetTime', 'placeBetTime');
	        $this->player_model->addIndex($this->tableName, 'idx_roundId', 'roundId');
	        $this->player_model->addIndex($this->tableName, 'idx_userName', 'userName');
	        $this->player_model->addIndex($this->tableName, 'idx_actionStatusID', 'actionStatusID');
	        $this->player_model->addIndex($this->tableName, 'idx_userTransNumber', 'userTransNumber');
	        $this->player_model->addIndex($this->tableName, 'idx_externalBalanceActionID', 'externalBalanceActionID');
	        $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
