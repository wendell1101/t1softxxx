<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_total_cashback_to_aff_monthly_earnings_20210923 extends CI_Migration {

    private $tableName = 'aff_monthly_earnings';

    public function up() {
        
        $field = array(
            'total_cashback' => array(
				'type' => 'DOUBLE',
				'null' => false,
				'default' => 0,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('total_cashback', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('total_cashback', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'total_cashback');
            }
        }
    }
}