<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fg_seamless_transaction_logs_20210127 extends CI_Migration {
    
    private $tableNames = [
        'fg_seamless_gamelogs_per_transaction',
        'fg_seamless_thb1_gamelogs_per_transaction',
        'fg_seamless_gamelogs',
        'fg_seamless_thb1_gamelogs'
	];

    public function up() {
        $this->load->model('player_model');

        $fields = [
            'elapsed_time' => [
                "type" => "INT",
                "null" => true,                
            ],
        ];

        foreach ($this->tableNames as $table) {
            if(!$this->db->field_exists('elapsed_time', $table)){
                $this->dbforge->add_column($table, $fields);
            }
        }
    }

    public function down() {
        foreach ($this->tableNames as $table) {
            if($this->db->field_exists('elapsed_time', $table)) {
                $this->dbforge->drop_column($table, 'elapsed_time');
            }
        }
    }
}