<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_withdraw_conditions_20200817 extends CI_Migration
{
	private $tableName = 'withdraw_conditions';

    public function up() {

        $fields = array(
            'is_deducted_from_calc_cashback' => array(
                'type' => 'TINYINT',
                'constraint' => '4',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('is_deducted_from_calc_cashback', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('is_deducted_from_calc_cashback', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'is_deducted_from_calc_cashback');
            }
        }
    }
}