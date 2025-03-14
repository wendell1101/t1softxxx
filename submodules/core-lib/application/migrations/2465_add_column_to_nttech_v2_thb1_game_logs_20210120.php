<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_nttech_v2_thb1_game_logs_20210120 extends CI_Migration {
    
    private $tableNames = [
        'nttech_v2_thb1_game_logs',
        'nttech_v2_cny1_game_logs',
        'nttech_v2_inr1_game_logs',
        'nttech_v2_game_logs'
	];
    public function up() {
        $this->load->model('player_model');

        $fields1 = array(
            'settlestatus' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        foreach ($this->tableNames as $table) {
            if(!$this->db->field_exists('settlestatus', $table)){
                $this->dbforge->add_column($table, $fields1);
            }
        }
        
    }

    public function down() {
        foreach ($this->tableNames as $table) {
            if($this->db->field_exists('settlestatus', $table)){
                $this->dbforge->drop_column($table, 'settlestatus');
            }
        }
    }
}