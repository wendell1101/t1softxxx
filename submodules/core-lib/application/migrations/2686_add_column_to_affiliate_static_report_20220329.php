<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_affiliate_static_report_20220329 extends CI_Migration
{
    private $tableName = 'affiliate_static_report';

    public function up() {

        $fields = array(
            'cashback_revenue' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('cashback_revenue', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cashback_revenue', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cashback_revenue');
            }
        }
    }
}