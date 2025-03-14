<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_walletaccount_20200713 extends CI_Migration
{
    private $tableName = 'walletaccount';

    public function up() {

        $fields = array(
            'withdrawal_fee_amount' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('withdrawal_fee_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('withdrawal_fee_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'withdrawal_fee_amount');
            }
        }
    }
}