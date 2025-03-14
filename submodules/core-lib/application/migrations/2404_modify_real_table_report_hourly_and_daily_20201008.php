<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_modify_real_table_report_hourly_and_daily_20201008 extends CI_Migration
{
    private $tableName = 'real_table_report_hourly';
    private $tableName2 = 'real_table_report_daily';

    public function up() {

        $fields = array(
            'hour_within' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ),
            'date_hour' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('hour_within', $this->tableName) && !$this->db->field_exists('date_hour', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
            if($this->db->field_exists('hour_within', $this->tableName2) && $this->db->field_exists('date_hour', $this->tableName2)){
                $this->dbforge->drop_column($this->tableName2, 'hour_within');
                $this->dbforge->drop_column($this->tableName2,'date_hour');
            }
        }

    }

    public function down() {
    }
}