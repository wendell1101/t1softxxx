<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sexy_baccarat_transactions_20220506 extends CI_Migration {

    private $tableName = 'sexy_baccarat_transactions';

    public function up() {
        
        $field = array(
            "action_status" => [
                "type" => "TINYINT",
                "null" => true
            ],
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('action_status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('action_status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'action_status');
            }
        }
    }
}