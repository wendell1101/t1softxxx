<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_walletaccount_20210719 extends CI_Migration {

    private $tableName ='walletaccount';

    public function up() {
        $fields = array(
            'bankName' => array(
                'type' => 'varchar',
                'constraint' => 1000,
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bankName', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
    }
}