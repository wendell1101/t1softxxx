<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_evolution_seamless_wallet_transactions_20240823 extends CI_Migration {
    private $tableNames = [
        'evolution_seamless_wallet_transactions',
        'evolution_btg_seamless_wallet_transactions',
        'evolution_netent_seamless_wallet_transactions',
        'evolution_nlc_seamless_wallet_transactions',
        'evolution_redtiger_seamless_wallet_transactions'
    ];

    public function up() {

        $field1 = array(
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        foreach($this->tableNames as $tableName){
            if($this->utils->table_really_exists($tableName)){
                if(!$this->db->field_exists('player_id', $tableName) ){
                    $this->dbforge->add_column($tableName, $field1);
                    $this->player_model->addIndex($tableName, 'idx_player_id', 'player_id');
                }
            }
        }
    }

    public function down() {
    }
}