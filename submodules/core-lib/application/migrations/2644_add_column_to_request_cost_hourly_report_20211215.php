<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_request_cost_hourly_report_20211215 extends CI_Migration
{
    private $tableName = 'request_cost_hourly_report';

    public function up()
    {
        $field1 = array(
            'slow_25s' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        $field2 = array(
            'p_25s' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName))
        {
            if(!$this->db->field_exists('slow_25s', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }

            if(!$this->db->field_exists('p_25s', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName))
        {
            if($this->db->field_exists('slow_25s', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'slow_25s');
            }

            if($this->db->field_exists('p_25s', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'p_25s');
            }
        }
    }
}
