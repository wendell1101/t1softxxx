<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_s128_game_logs_20201119 extends CI_Migration {

	private $tableName = 's128_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'ticket_id' => array(
				'type' => 'BIGINT',
				'null' => false,				
            ),
			'login_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => false,
            ),
            'arena_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'arena_name_cn' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'match_no' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
            ),
            'match_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'match_date' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
            'fight_no' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
            ),
            'fight_datetime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
            'meron_cock' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'meron_cock_cn' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'wala_cock' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'wala_cock_cn' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'bet_on' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'odds_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'odds_asked' => array(
				'type' => 'double',
                'null' => true,
            ),
            'odds_given' => array(
				'type' => 'double',
                'null' => true,
            ),
            'stake' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
            ),
            'stake_money' => array(
				'type' => 'double',
                'null' => true,
            ),
            'balance_open' => array(
				'type' => 'double',
                'null' => true,
            ),
            'balance_close' => array(
				'type' => 'double',
                'null' => true,
            ),
            'created_datetime' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
            'fight_result' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'status' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
            ),
            'winloss' => array(
				'type' => 'double',
                'null' => true,
            ),
            'comm_earned' => array(
				'type' => 'double',
                'null' => true,
            ),
            'payout' => array(
				'type' => 'double',
                'null' => true,
            ),
            'balance_open1' => array(
				'type' => 'double',
                'null' => true,
            ),
            'balance_close1' => array(
				'type' => 'double',
                'null' => true,
            ),
            'processed_datetime' => array(
				'type' => 'DATETIME',
				'null' => true,
            ),
            'game_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '30',
				'null' => true,
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

		if(!$this->utils->table_really_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_s128_login_id', 'login_id');
            

            $this->player_model->addIndex($this->tableName, 'idx_s128_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_s128_processed_datetime', 'processed_datetime');
            $this->player_model->addIndex($this->tableName, 'idx_s128_created_datetime', 'created_datetime');


	        $this->player_model->addUniqueIndex($this->tableName, 'idx_sv388_external_uniqueid', 'external_uniqueid');
        }        
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
