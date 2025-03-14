<?php

defined("BASEPATH") OR exit("No direct script access allowed");
// submodules/core-lib/application/migrations/2821_add_columns_to_affiliates_20230306.php
class Migration_add_columns_to_affiliates_20230306 extends CI_Migration
{
	private $tableName = 'affiliates';


    public function up() {

        $field1 = array(
            'is_hide' => array(
                'type' => 'BOOLEAN',
                'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('is_hide', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('is_hide', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'is_hide');
            }
        }
    }
}
