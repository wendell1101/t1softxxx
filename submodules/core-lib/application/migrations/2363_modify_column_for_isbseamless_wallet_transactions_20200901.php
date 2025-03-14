<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_isbseamless_wallet_transactions_20200901 extends CI_Migration {

    private $tableName = [
        'isbseamless_cny1_wallet_transactions',
        'isbseamless_idr1_wallet_transactions',
        'isbseamless_myr1_wallet_transactions',
        'isbseamless_thb1_wallet_transactions',
        'isbseamless_usd1_wallet_transactions',
        'isbseamless_vnd1_wallet_transactions',
        'isbseamless_wallet_transactions'
    ];

    public function up() {
        foreach ($this->tableName as $tableName) {
            if($this->utils->table_really_exists($tableName)){
                $field = array(
                    'transaction_id' => array(
                        'name' => 'transactionid',
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => true,
                    )
                );
                $field2 = array(
                    'before_balance' => array(
                        'type' => 'DOUBLE',
                        'null' => true,
                    ),
                );

                if($this->db->field_exists('transaction_id', $tableName)){
                    $this->dbforge->modify_column($tableName, $field);
                }
                if(!$this->db->field_exists('before_balance', $tableName)){
                    $this->dbforge->add_column($tableName, $field2);
                }
            }
        }
    }

    public function down() {
    }
}