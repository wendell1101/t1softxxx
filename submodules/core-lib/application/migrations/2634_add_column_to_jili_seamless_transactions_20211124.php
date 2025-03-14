<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_jili_seamless_transactions_20211124 extends CI_Migration
{
    
	private $tableName = 'jili_seamless_wallet_transactions';

    public function up()
    {
        $fields = array(
            'balance_adjustment_amount' => array(
                'type' => 'double',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName))
        {
            if(!$this->db->field_exists('balance_adjustment_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down()
    {
    }
}
