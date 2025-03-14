<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_yabo_gamelogs_20210219 extends CI_Migration {

	private $origTableName = 'yabo_gamelogs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'uniqueId' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			'playerId' => array(
				'type' => 'INT',
				'null' => true,
			),
			'playerName' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'agentId' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'betAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'validBetAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'netAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'beforeAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
			'createdAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
			'netAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
			'recalcuAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
			'updatedAt' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
			'gameTypeId' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'platformId' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'platformName' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'betStatus' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'betFlag' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'betPointId' => array(
				'type' => 'SMALLINT',
				'null' => true,
			),
			'judgeResult' => array(
				'type' => 'VARCHAR',
				'constraint' => '300',
				'null' => true,
			),
			'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'tableCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'roundNo' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bootNo' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'loginIp' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'deviceType' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'deviceId' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'recordType' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'gameMode' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'signature' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'nickName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'dealerName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'tableName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'addstr1' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'addstr2' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'agentCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'agentName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'betPointName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'gameTypeName' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'payAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'adddec1' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'adddec2' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'adddec3' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'result' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'startid' => array(
				'type' => 'BIGINT',
				'null' => true,
			),
			# SBE additional info
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'login_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'game_externalid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
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
				'constraint' => '100',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            )
		);

	    if(!$this->db->table_exists($this->origTableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->origTableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->origTableName, 'idx_playerName', 'playerName');
	        $this->player_model->addIndex($this->origTableName, 'idx_roundNo', 'roundNo');
	        $this->player_model->addIndex($this->origTableName, 'idx_gameTypeId', 'gameTypeId');
	        $this->player_model->addIndex($this->origTableName, 'idx_createdAt', 'createdAt');
	        $this->player_model->addIndex($this->origTableName, 'idx_netAt', 'netAt');
	        $this->player_model->addIndex($this->origTableName, 'idx_updatedAt', 'updatedAt');
	        $this->player_model->addIndex($this->origTableName, 'idx_game_externalid', 'game_externalid');
	        $this->player_model->addIndex($this->origTableName, 'idx_login_name', 'login_name');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_uniqueId', 'uniqueId');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->origTableName);
	}
}
