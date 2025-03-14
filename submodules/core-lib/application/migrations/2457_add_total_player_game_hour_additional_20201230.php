<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_total_player_game_hour_additional_20201230 extends CI_Migration {

    public function up()
    {
        $fields = [
            'uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'rent' => [
                'type' => 'DOUBLE',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ]
        ];

        $this->load->model('player_model');

        if(! $this->utils->table_really_exists('total_player_game_hour_additional')){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('uniqueid',TRUE);
            $this->dbforge->create_table('total_player_game_hour_additional');
        }
        if(! $this->utils->table_really_exists('total_player_game_minute_additional')){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('uniqueid',TRUE);
            $this->dbforge->create_table('total_player_game_minute_additional');
        }

    }

    public function down()
    {
    }
}