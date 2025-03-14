<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_transfer_code_for_sbobet_seamless_game_transactions_20210408 extends CI_Migration {

    private $tableName='sbobet_seamless_game_transactions';

    public function up() {
        $field = array(
            'transfer_code' => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transfer_code', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}