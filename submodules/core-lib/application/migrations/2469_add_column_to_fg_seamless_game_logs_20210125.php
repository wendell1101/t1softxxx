<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fg_seamless_game_logs_20210125 extends CI_Migration {
    
    private $tableNames = [
        'fg_seamless_gamelogs',
        'fg_seamless_thb1_gamelogs'
	];

    public function up() {
        $this->load->model('player_model');

        $fields = [
            'sbe_playerid' => [
                "type" => "INT",
                "null" => true,                
            ],
        ];

        foreach ($this->tableNames as $table) {
            if(!$this->db->field_exists('sbe_playerid', $table)){
                $this->dbforge->add_column($table, $fields);
            }
        }
    }

    public function down() {
        foreach ($this->tableNames as $table) {
            if($this->db->field_exists('sbe_playerid', $table)) {
                $this->dbforge->drop_column($table, 'sbe_playerid');
            }
        }
    }
}