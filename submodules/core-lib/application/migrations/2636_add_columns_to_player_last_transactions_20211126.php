<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_player_last_transactions_20211126 extends CI_Migration {

    private $tableName = 'player_last_transactions';

    public function up() {
        $fields = array(
            'withdrawal_transactions_id' => array(
				'type' => 'INT',
				'null' => true
			),
            'promo_transactions_id' => array(
                'type' => 'INT',
				'null' => true
			)
        );

        if(!$this->db->field_exists('withdrawal_transactions_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }

        if($this->db->field_exists('transactions_id', $this->tableName)) {
            $rename_field = array(
                'transactions_id' => array(
                    'name' => 'deposit_transaction_id',
                    'type' => 'INT',
                    'null' => true
                ),
            );
            $this->dbforge->modify_column($this->tableName, $rename_field);
        }

    }

    public function down() {
        if($this->db->field_exists('withdrawal_transactions_id', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'withdrawal_transactions_id');
        }

        if($this->db->field_exists('deposit_transaction_id', $this->tableName)) {
            $rename_field = array(
                'deposit_transaction_id' => array(
                    'name' => 'transactions_id',
                    'type' => 'INT',
                    'null' => true
                ),
            );
            $this->dbforge->modify_column($this->tableName, $rename_field);
        }
    }
}
