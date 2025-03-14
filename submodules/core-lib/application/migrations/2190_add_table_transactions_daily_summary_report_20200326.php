<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_transactions_daily_summary_report_20200326 extends CI_Migration {

	private $tableName = 'transactions_daily_summary_report';

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
            'username' => array(
				'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ),
            'sync_date' => array(
                'type' => 'DATE',
                'null' => false,
            ),
            'total_initial_balance' => array(
				'type' => 'TEXT',
                'null' => false,
            ),
            'total_deposit' => array(
				'type' => 'double',
                'null' => true,
            ),
            'total_manual_add_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_withdrawal' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_manual_subtract_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_add_bonus' => array(
				'type' => 'double',
                'null' => true,
            ),
            'total_referral_bonus' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_vip_bonus' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_subtract_bonus' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_add_cashback' => array(
                'type' => 'double',
                'null' => true,
            ),
            'total_win' => array(
				'type' => 'double',
                'null' => true,
            ),
            'total_loss' => array(
				'type' => 'double',
                'null' => true,	
            ),
            'end_balance' => array(
				'type' => 'double',
                'null' => true,
            ),
            'latest_balance_record' => array(
				'type' => 'TEXT',
                'null' => false,
            ),
            'balance_validation' => array(
				'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableName);
			# Add Index
	        $this->load->model('player_model');
	        $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
	        $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_sync_date', 'sync_date');
	    }
	}

	public function down() {
		$this->dbforge->drop_table($this->tableName);
	}
}
