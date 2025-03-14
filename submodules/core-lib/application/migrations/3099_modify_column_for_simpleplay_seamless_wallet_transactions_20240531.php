<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_simpleplay_seamless_wallet_transactions_20240531 extends CI_Migration {

    private $tableNames = [
        'simpleplay_seamless_wallet_transactions'
    ];

    public function up() {
        $fields1 = array(
            "gameid" => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ]
        );

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                if($this->db->field_exists('gameid', $tableName)){
                    $this->dbforge->modify_column($tableName, $fields1);
                }
            }

        }


    }

    public function down() {
    }
}