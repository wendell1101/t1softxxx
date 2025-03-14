<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_vipsettingcashbackrule_20200713 extends CI_Migration
{
    private $tableName = 'vipsettingcashbackrule';

    public function up() {

        $fields1 = array(
            'max_monthly_withdrawal' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('max_monthly_withdrawal', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('max_monthly_withdrawal', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'max_monthly_withdrawal');
            }
        }
    }
}