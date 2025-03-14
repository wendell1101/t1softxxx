<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_summary_game_total_bet_daily_20240916 extends CI_Migration {

    private $tableName = 'summary_game_total_bet_daily';

    public function up() {
        if(!$this->db->table_exists($this->tableName)){
            $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL("CREATE TABLE {$this->tableName} LIKE summary_game_total_bet");
        }
    }

    public function down() {
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}