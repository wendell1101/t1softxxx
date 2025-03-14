<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_account_20240308 extends CI_Migration {
	private $tableName = 'payment_account';

    public function up() {
        $field1 = array(
            'bonus_percent_on_deposit_amount' => array(
                'type' => 'DOUBLE',
				'null' => true,
				'default' => 0,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('bonus_percent_on_deposit_amount', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonus_percent_on_deposit_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonus_percent_on_deposit_amount');
            }
        }
    }
}
///END OF FILE/////