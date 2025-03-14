<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_isbseamless_wallet_transactions_20200903 extends CI_Migration {

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
                    'transaction_status' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => true,
                    )
                );
                $field2 = array(
                    'currency' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    )
                );

                if(!$this->db->field_exists('transaction_status', $tableName)){
                    $this->dbforge->add_column($tableName, $field);
                }
                 if(!$this->db->field_exists('currency', $tableName)){
                    $this->dbforge->add_column($tableName, $field2);
                }
            }
        }
    }

    public function down() {
    }
}