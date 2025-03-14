<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_evolution_seamless_wallet_transactions_20240429 extends CI_Migration {

    private $tableNames = ['evolution_seamless_wallet_transactions', 'evolution_seamless_thb1_wallet_transactions'];

    public function up() {
        $column = [
            'gameId' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
        ];

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                if ($this->db->field_exists('gameId', $tableName)) {
                    $this->dbforge->modify_column($tableName, $column);
                }
            }

        }


    }

    public function down() {
    }
}