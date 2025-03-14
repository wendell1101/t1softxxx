<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_redirect_to_column_to_affiliates_table_20210412 extends CI_Migration {

    private $tableName='affiliates';

    public function up() {
        $field = array(
            'redirect' => array(
                "type" => "TINYINT",
                'null' => false,
                'default' => 0,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('redirect', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}