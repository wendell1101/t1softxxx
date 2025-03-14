<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_pragmaticplay_tables_20200416 extends CI_Migration {
    private $tableNames = [
        'pragmaticplay_cny1_game_logs',
        'pragmaticplay_cny2_game_logs',
        'pragmaticplay_game_logs',
        'pragmaticplay_idr1_game_logs',
        'pragmaticplay_idr2_game_logs',
        'pragmaticplay_idr3_game_logs',
        'pragmaticplay_idr4_game_logs',
        'pragmaticplay_idr5_game_logs',
        'pragmaticplay_idr6_game_logs',
        'pragmaticplay_idr7_game_logs',
        'pragmaticplay_livedealer_cny1_gamelogs',
        'pragmaticplay_livedealer_idr1_gamelogs',
        'pragmaticplay_livedealer_myr1_gamelogs',
        'pragmaticplay_livedealer_thb1_gamelogs',
        'pragmaticplay_livedealer_usd1_gamelogs',
        'pragmaticplay_livedealer_vnd1_gamelogs',
        'pragmaticplay_myr1_game_logs',
        'pragmaticplay_myr2_game_logs',
        'pragmaticplay_thb1_game_logs',
        'pragmaticplay_thb2_game_logs',
        'pragmaticplay_vnd1_game_logs',
        'pragmaticplay_vnd2_game_logs',
        'pragmaticplay_vnd3_game_logs',
    ];

    public function up() {
        $this->load->model('player_model');

        foreach ($this->tableNames as $table) {
            if(!$this->player_model->existsIndex($table, 'idx_end_date') && $this->db->field_exists('end_date', $table)) {
                $this->player_model->addIndex($table, 'idx_end_date', 'end_date');
            }
        }
    }

    public function down() {

    }
}
