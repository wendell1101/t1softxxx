<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_request_cost_hourly_report_2021121518 extends CI_Migration
{
    private $tableName = 'request_cost_hourly_report';

    public function up()
    {
        $field = array(
            'total_error' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName))
        {
            if(!$this->db->field_exists('total_error', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName))
        {
            if($this->db->field_exists('total_error', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'total_error');
            }
        }
    }
}
