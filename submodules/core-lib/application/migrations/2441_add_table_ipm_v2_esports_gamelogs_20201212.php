<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_ipm_v2_esports_gamelogs_20201212 extends CI_Migration {

	private $tableName = 'ipm_v2_esports_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
			),
			'Provider' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'GameID' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetId' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'WagerCreationDateTime' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'PlayerId' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'StakeAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'MemberExposure' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'PayoutAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'WinLoss' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'OddsType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'WagerType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Platform' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'isSettled' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'isConfirmed' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'isCancelled' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetTradeStatus' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetTradeCommission' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'BetTradeBuybackAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'ComboType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'LastUpdatedDate' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'DetailItems' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'response_result_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'SportsName' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'last_sync_time' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),

		);

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('last_sync_time');
            $this->dbforge->create_table($this->tableName);
            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->tableName, 'idx_WagerCreationDateTime','WagerCreationDateTime');
            $this->player_model->addIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid',true);
            $this->player_model->addIndex($this->tableName, 'idx_BetId', 'BetId',true);
            
        }
	}

	public function down() {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
	}
}
