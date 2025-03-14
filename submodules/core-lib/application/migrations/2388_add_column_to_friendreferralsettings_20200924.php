<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_friendreferralsettings_20200924 extends CI_Migration
{
	private $tableName = 'friendreferralsettings';

    public function up() {

        $fields = array(
            'referralDetails' => array(
                'type' => 'text',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('referralDetails', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('referralDetails', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'referralDetails');
            }
        }
    }
}