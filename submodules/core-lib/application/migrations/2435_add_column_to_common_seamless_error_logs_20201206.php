<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_common_seamless_error_logs_20201206 extends CI_Migration {

    private $tableName = 'common_seamless_error_logs';

    public function up() {
        $fields1 = array(
            'error_id' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('error_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields1);
        }
    }

    public function down() {

        if($this->db->field_exists('error_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'error_id');
        }
    }
}