<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_evolution_seamless_wallet_transactions_20240527 extends CI_Migration {

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
            "game_platform_id" => [
                "type" => "INT",
                "null" => true
            ]
        );
       

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                if(!$this->db->field_exists('game_platform_id', $tableName)){
                    $this->dbforge->add_column($tableName, $fields1);
                    $this->player_model->addIndex($tableName, 'idx_game_platform_id', 'game_platform_id');
                }
            }

        }


    }

    public function down() {
    }
}