<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promorules_20231206 extends CI_Migration {

    private $tableName = 'promorules';

    public function up() {
        $field = [
            'donot_allow_exists_any_bet_after_deposit' => [
                'type' => 'TINYINT',
                'null' => false,
                'constrain' => 1,
                'default' => 0
            ],
        ];

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('donot_allow_exists_any_bet_after_deposit', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('donot_allow_exists_any_bet_after_deposit', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'donot_allow_exists_any_bet_after_deposit');
            }
        }
    }
}