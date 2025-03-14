<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_champion_sports_game_logs_20200619 extends CI_Migration {

	private $origTableName = 'champion_sports_game_logs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'account' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'bet_time' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'bid' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'detail' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'lang' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
			),
			'mid' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'numlines' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'oddstype' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'potential_win' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'settle_time' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'stake' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'status' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'transaction' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
			),
			'updated' => array(
				'type' => 'DATETIME',
				'null' => true,
			),
			'valid' => array(
				'type' => 'VARCHAR',
				'constraint' => '5',
				'null' => true,
			),
			'valid_stake' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
			'win' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
            # SBE additional info
            'md5_sum' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
                'null' => true,
            ),
            'extra' => array(
                'type' => 'json',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'constraint' => '11',
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
		);

	    if(!$this->db->table_exists($this->origTableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->origTableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->origTableName, 'idx_bid', 'bid');
	        $this->player_model->addIndex($this->origTableName, 'idx_transaction', 'transaction');
	        $this->player_model->addIndex($this->origTableName, 'idx_account', 'account');
	        $this->player_model->addUniqueIndex($this->origTableName, 'idx_external_uniqueid', 'external_uniqueid');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->origTableName);
	}
}
