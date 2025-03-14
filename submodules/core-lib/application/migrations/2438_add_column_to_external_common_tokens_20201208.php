<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_external_common_tokens_20201208 extends CI_Migration {

    private $tableName = 'external_common_tokens';

    public function up() {
        $field = array(
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
        );
        if(!$this->db->field_exists('updated_at', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
    }

    public function down() {

        if($this->db->field_exists('updated_at', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'updated_at');
        }
    }
}