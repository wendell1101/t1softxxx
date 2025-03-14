<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_external_unique_id_flash_tech_game_logs_20200818 extends CI_Migration
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

    private $matchidx = [
        'idx_flashtech_MatchID',
        'idx_flashtech_thb1_MatchID',
        'idx_flashtech_idr1_MatchID',
        'idx_flashtech_cny1_MatchID',
        'idx_flashtech_myr1_MatchID',
        'idx_flashtech_vnd1_MatchID',
        'idx_flashtech_usd1_MatchID'
    ];


    public function up() {

        $fields = array(
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
        );
        $this->load->model('player_model');
        foreach($this->tableNames as $key => $table){
            if($this->utils->table_really_exists($table)){

                if(!$this->db->field_exists('external_unique_id', $table) && !$this->db->field_exists('elapsed_time', $table)){
                    $this->dbforge->add_column($table, $fields);
                }

                if($this->db->field_exists('external_unique_id', $table)){
                    # add Index
                    $this->player_model->addUniqueIndex($table, 'idx_flashtech_external_unique_id', 'external_unique_id');
                }

                if($this->db->field_exists('MatchID', $table)){
                    if(isset($this->matchidx[$key])){
                        # remove index
                        $this->player_model->dropIndex($table, $this->matchidx[$key], 'MatchID');
                    }
                }
            }
        }
    }

    public function down() {
    }
}