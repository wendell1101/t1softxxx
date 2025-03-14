<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_before_and_after_balance_to_seamless_free_bet_record_20221121 extends CI_Migration
{
	private $tableName = 'seamless_free_bet_record';


    public function up() {
        $field1 = array(
            'before_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        $field2 = array(
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('before_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('before_balance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'before_balance');
            }
        }

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'after_balance');
            }
        }
    }
}