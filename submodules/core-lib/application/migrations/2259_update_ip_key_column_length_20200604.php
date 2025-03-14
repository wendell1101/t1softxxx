<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_ip_key_column_length_20200604 extends CI_Migration {

    private $tableName = 'registration_request_limit';    

    public function up() {
        //modify column size
        $fields = array(
            'ip_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
        );
        $this->dbforge->modify_column($this->tableName, $fields);
    }

    public function down() {
        // not able to rollback due to data truncation
    }
}