<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_sexy_baccarat_transactions_20201104 extends CI_Migration {

    private $tableName = 'sexy_baccarat_transactions';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $field = array(
                'cancel_before' => array(
                    "type" => "DOUBLE",
                    "null" => true
                )
            );
            $field2 = array(
                'cancel_after' => array(
                    "type" => "DOUBLE",
                    "null" => true
                )
            );

            if(!$this->db->field_exists('cancel_before', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
             if(!$this->db->field_exists('cancel_after', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
    }
}