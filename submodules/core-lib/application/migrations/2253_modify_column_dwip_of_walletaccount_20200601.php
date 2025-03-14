<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_dwip_of_walletaccount_20200601 extends CI_Migration {

    private $tableName='walletaccount';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'dwIp' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '45',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}