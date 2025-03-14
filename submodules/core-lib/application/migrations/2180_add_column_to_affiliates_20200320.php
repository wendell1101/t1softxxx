<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_affiliates_20200320 extends CI_Migration
{
	private $tableName = 'affiliates';

    public function up() {

        $fields = array(
            'isActiveSubAffLink' => array(
                'type' => 'tinyint',
                'null' => false,
                'default' => 1,
            ),
        );

        if(!$this->db->field_exists('isActiveSubAffLink', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('isActiveSubAffLink', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'isActiveSubAffLink');
        }
    }
}