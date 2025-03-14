<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_real_tables_20210205 extends CI_Migration {
    
    private $tableNames = [
        'real_table_report_minutes',
        'real_table_report_hourly',
        'real_table_report_daily'
	];
    public function up() {
        $this->load->model('player_model');

        $column1 = array(
            'player_id' => array(
                "type" => "BIGINT",
                "null" => false,
            ),
        );

        $column2 = array(
            'game_type_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        foreach ($this->tableNames as $table) {
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('player_id', $table)){
                    $this->dbforge->add_column($table, $column1);
                }
                if(!$this->db->field_exists('game_type_id', $table)){
                    $this->dbforge->add_column($table, $column2);
                }
            }
        }
        
    }

    public function down() {
        
    }
}