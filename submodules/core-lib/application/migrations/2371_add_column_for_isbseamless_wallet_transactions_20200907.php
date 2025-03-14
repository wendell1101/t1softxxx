<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_isbseamless_wallet_transactions_20200907 extends CI_Migration {

    private $tableName = [
        'isbseamless_cny1_wallet_transactions',
        'isbseamless_idr1_wallet_transactions',
        'isbseamless_myr1_wallet_transactions',
        'isbseamless_thb1_wallet_transactions',
        'isbseamless_usd1_wallet_transactions',
        'isbseamless_vnd1_wallet_transactions',
        'isbseamless_wallet_transactions'
    ];

    public function up()
    {
        foreach ($this->tableName as $tableName) {
            if($this->utils->table_really_exists($tableName)){
                $fields = array(
                    'operator' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => true,
                    ),
                );

                if(!$this->db->field_exists('operator', $tableName)){
                    $this->dbforge->add_column($tableName, $fields);
                }
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'operator');
    }
}