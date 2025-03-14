<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_habanero_transactions_currency_20200825 extends CI_Migration {

	private $tableNames = [
		
		'habanero_transactions_idr2',
		'habanero_transactions_idr3',
		'habanero_transactions_idr4',
		'habanero_transactions_idr5',
		'habanero_transactions_idr6',
		'habanero_transactions_idr7',

		'habanero_transactions_cny1',
		'habanero_transactions_cny2',
		'habanero_transactions_cny3',
		'habanero_transactions_cny4',
		'habanero_transactions_cny5',
		'habanero_transactions_cny6',
		'habanero_transactions_cny7',

		'habanero_transactions_thb1',
		'habanero_transactions_thb2',
		'habanero_transactions_thb3',
		'habanero_transactions_thb4',
		'habanero_transactions_thb5',
		'habanero_transactions_thb6',
		'habanero_transactions_thb7',

		'habanero_transactions_myr1',
		'habanero_transactions_myr2',
		'habanero_transactions_myr3',
		'habanero_transactions_myr4',
		'habanero_transactions_myr5',
		'habanero_transactions_myr6',
		'habanero_transactions_myr7',

		'habanero_transactions_vnd1',
		'habanero_transactions_vnd2',
		'habanero_transactions_vnd3',
		'habanero_transactions_vnd4',
		'habanero_transactions_vnd5',
		'habanero_transactions_vnd6',
		'habanero_transactions_vnd7',

		'habanero_transactions_usd1',
		'habanero_transactions_usd2',
		'habanero_transactions_usd3',
		'habanero_transactions_usd4',
		'habanero_transactions_usd5',
		'habanero_transactions_usd6',
		'habanero_transactions_usd7',
	];

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'dtsent' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'brandgameid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'keyname' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'auth_username' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'auth_passkey' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'auth_machinename' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'auth_locale' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'auth_brandid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'token' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'accountid' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'customplayertype' => array(
                "type" => "TINYINT",
                "null" => true
			),
			'gameinstanceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'friendlygameinstanceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'isretry' => array(
                "type" => "TINYINT",
                "null" => true		
			),
			'retrycount' => array(
                "type" => "TINYINT",
                "null" => true
			),
			'isrefund' => array(
                "type" => "TINYINT",
                "null" => true	
			),
			'isrecredit' => array(
                "type" => "TINYINT",
                "null" => true
			),
            "funds_debitandcredit" => [
                "type" => "TINYINT",
                "null" => true
			],
			'fundinfo_gamestatemode' => array(
                "type" => "TINYINT",
                "null" => true
			),
			'fundinfo_transferid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'fundinfo_currencycode' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'fundinfo_amount' => array(
                "type" => "DOUBLE",
                "null" => true
			),
			'fundinfo_jpwin' => array(
                "type" => "TINYINT",
                "null" => true	
			),
			'fundinfo_jpcont' => array(
                "type" => "DOUBLE",
                "null" => true
			),
			'fundinfo_isbonus' => array(
				"type" => "TINYINT",
                "null" => true
			),
			'fundinfo_dtevent' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'fundinfo_initialdebittransferid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_keyname' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'gamedetails_gametypeid' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'gamedetails_gametypename' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'gamedetails_brandgameid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_gamesessionid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_gameinstanceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_friendlygameinstanceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gamedetails_channel' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
			'gamedetails_device' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'gamedetails_browser' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),

			'bonusdetails_bonusbalanceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bonusdetails_couponid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bonusdetails_coupontypeid' => array(
                "type" => "TINYINT",
                "null" => true
			),
			'bonusdetails_couponcode' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),

			'raw_data' => array(
                'type' => 'TEXT',
                'null' => true,
			),
			'is_valid_transaction' => array(
                "type" => "TINYINT",
				"null" => true,
				'default' => 0,
			),
			'player_id' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => false,
            ),	
            'elapsed_time' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),		
            'trans_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ),

            'balance_before' => array(
                'type' => 'double',
                'null' => true,
            ),
            'balance_after' => array(
                'type' => 'double',
                'null' => true,
            ),
            'fundinfo_originaltransferid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'fundinfo_dtevent_parsed' => array(
                "type" => "DATETIME",
                "null" => true
            ),            
            'dtsent_parsed' => array(
                "type" => "DATETIME",
                "null" => true
            ),
            'is_refunded' => array(
				'type' => 'TINYINT(1)',
				'null' => false,
				'default' => 0
			),
            'fundinfo_jpid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),

			'altcredittype' => array(
                "type" => "TINYINT",
                "null" => true		
			),
			'description' => array(
                "type" => "TEXT",
                "null" => true		
			),
			'tournamentdetails_score' => array(
                'type' => 'double',
                'null' => true,	
			),
			'tournamentdetails_rank' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,		
			),
			'tournamentdetails_tournamenteventid' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
			),
			'fundinfo_accounttransactiontype' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
			),
			'fundinfo_gameinfeature' => array(
                "type" => "TINYINT",
                "null" => true
			),
			'fundinfo_lastbonusaction' => array(
                "type" => "TINYINT",
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
		);

		foreach($this->tableNames as $tableName){			
			if(!$this->utils->table_really_exists($tableName)){	
				$this->dbforge->add_field($fields);
				$this->dbforge->add_key('id', TRUE);
				$this->dbforge->create_table($tableName);
				
				# Add Index
				$this->load->model('player_model');
				$this->player_model->addIndex($tableName, 'idx_fundinfo_transferid', 'fundinfo_transferid');
				$this->player_model->addIndex($tableName, 'idx_gamedetails_gametypeid', 'gamedetails_gametypeid');
				$this->player_model->addIndex($tableName, 'idx_gamedetails_gamesessionid', 'gamedetails_gamesessionid');
				$this->player_model->addIndex($tableName, 'idx_player_id', 'player_id');	        
				$this->player_model->addIndex($tableName, 'idx_updated_at', 'updated_at');	        
				$this->player_model->addUniqueIndex($tableName, 'idx_external_uniqueid', 'external_uniqueid');
			}
		}
		
	}

	public function down() {
		foreach($this->tableNames as $tableName){
			if($this->utils->table_really_exists($tableName)){			
				$this->dbforge->drop_table($tableName);
			}
		}
	}
}
