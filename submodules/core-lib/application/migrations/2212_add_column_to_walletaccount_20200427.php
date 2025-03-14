<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_walletaccount_20200427 extends CI_Migration
{
    private $tableName = 'walletaccount';

    public function up() {

        $fields = array(
            'verifiedBankFlag' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('verifiedBankFlag', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('verifiedBankFlag', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'verifiedBankFlag');
        }
    }
}