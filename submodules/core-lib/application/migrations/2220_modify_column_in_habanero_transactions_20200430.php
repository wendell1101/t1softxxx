<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_in_habanero_transactions_20200430 extends CI_Migration {

    private $tableName='habanero_transactions';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'fundinfo_amount' => array(
                    "type" => "DOUBLE",
                    "null" => true
                ),
                'fundinfo_jpcont' => array(
                    "type" => "DOUBLE",
                    "null" => true
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}