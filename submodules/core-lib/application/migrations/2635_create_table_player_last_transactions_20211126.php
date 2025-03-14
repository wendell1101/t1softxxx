<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_create_table_player_last_transactions_20211126 extends CI_Migration {

    private $tableName = 'player_last_transactions';

    public function up() {
        $fields = array(
            'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
            'transactions_id' => array(
                'type' => 'INT',
                'null' => true
			),
            'player_id' => array(
                'type' => 'INT',
				'null' => true
			),
            'last_deposit_date' => array(
				'type' => 'DATETIME',
				'null' => true
			),
            'last_deposit_amount' => array(
				'type' => 'DOUBLE',
                'null' => true
			),
            'last_withdrawal_date' => array(
				'type' => 'DATETIME',
				'null' => true
			),
            'last_withdrawal_amount' => array(
                'type' => 'DOUBLE',
				'null' => true
			),
            'last_promo_date' => array(
                'type' => 'DATETIME',
				'null' => true
			),
            'last_promo_amount' => array(
                'type' => 'DOUBLE',
				'null' => true
			)
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_transactions_id', 'transactions_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
        }  
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
