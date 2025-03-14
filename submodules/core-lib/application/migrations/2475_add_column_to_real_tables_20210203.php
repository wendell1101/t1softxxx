<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_real_tables_20210203 extends CI_Migration {
    
    private $tableNames = [
        'real_table_report_minutes',
        'real_table_report_hourly',
        'real_table_report_daily'
	];
    public function up() {
        $this->load->model('player_model');

        $column = array(
            'bet_count' => array(
                "type" => "INT",
                "null" => true,   
            ),
        );

        foreach ($this->tableNames as $table) {
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('bet_count', $table)){
                    $this->dbforge->add_column($table, $column);
                }
            }
        }
        
    }

    public function down() {
        
    }
}