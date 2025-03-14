<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_unique_index_to_flash_tech_game_logs_20220708 extends CI_Migration
{
	private $tableNames = [
        'flash_tech_game_logs',
        'flash_tech_thb1_game_logs',
        'flash_tech_idr1_game_logs',
        'flash_tech_cny1_game_logs',
        'flash_tech_myr1_game_logs',
        'flash_tech_vnd1_game_logs',
        'flash_tech_usd1_game_logs'
    ];

    private $extUniqId = [
        'idx_flashtech_external_unique_id',
        'idx_flashtech_thb1_external_unique_id',
        'idx_flashtech_idr1_external_unique_id',
        'idx_flashtech_cny1_external_unique_id',
        'idx_flashtech_myr1_external_unique_id',
        'idx_flashtech_vnd1_external_unique_id',
        'idx_flashtech_usd1_external_unique_id'
    ];

    public function up() {

        foreach($this->tableNames as $key => $table){
            if($this->utils->table_really_exists($table)){
                if(!$this->player_model->existsIndex($table, $this->extUniqId[$key])){
                    if($this->db->field_exists('external_unique_id', $table)){
                        # add Index
                        $this->player_model->addUniqueIndex($table, $this->extUniqId[$key], 'external_unique_id');
                    }
                }
            }
        }
    }

    public function down() {
    }
}
