<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_response_result_id_of_common_seamless_wallet_transactions_20200417 extends CI_Migration {

    private $tableName='common_seamless_wallet_transactions';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'response_result_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '64',
                    'null' => true,
                ),
            );
            $this->dbforge->modify_column($this->tableName, $fields);
        }
    }

    public function down() {
    }
}