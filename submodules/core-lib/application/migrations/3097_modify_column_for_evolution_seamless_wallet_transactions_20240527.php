<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_evolution_seamless_wallet_transactions_20240527 extends CI_Migration {

    private $tableNames = [
        'evolution_seamless_wallet_transactions', 
        'evolution_seamless_thb1_wallet_transactions',
        'evolution_netent_seamless_wallet_transactions',
        'evolution_nlc_seamless_wallet_transactions',
        'evolution_redtiger_seamless_wallet_transactions',
        'evolution_btg_seamless_wallet_transactions',
    ];

    public function up() {
        $fields1 = array(
            "promoTransactionAmount" => [
                "type" => "DOUBLE",
                "null" => true
            ]
        );
        $fields2 = array(
            "promoTransactionVoucherId" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ]
        );
        $fields3 = array(
            "promoTransactionId" => [
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ]
        );

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                if(!$this->db->field_exists('promoTransactionAmount', $tableName)){
                    $this->dbforge->add_column($tableName, $fields1);
                }
                if(!$this->db->field_exists('promoTransactionVoucherId', $tableName)){
                    $this->dbforge->add_column($tableName, $fields2);
                    $this->player_model->addIndex($tableName, 'idx_promoTransactionVoucherId', 'promoTransactionVoucherId');
                }
                if(!$this->db->field_exists('promoTransactionId', $tableName)){
                    $this->dbforge->add_column($tableName, $fields3);
                    $this->player_model->addIndex($tableName, 'idx_promoTransactionId', 'promoTransactionId');
                }
            }

        }


    }

    public function down() {
    }
}