<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_real_tables_20210202 extends CI_Migration {
    
    private $tableNames = [
        'real_table_report_minutes',
        'real_table_report_hourly',
        'real_table_report_daily'
	];
    public function up() {
        $this->load->model('player_model');

        $field = array(
            'uniqueid' => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ),
        );

        foreach ($this->tableNames as $table) {
            if($this->utils->table_really_exists($table)){
                if($this->db->field_exists('uniqueid', $table)){
                    $this->dbforge->modify_column($table, $field);
                }
            }
        }
        
    }

    public function down() {
        
    }
}