<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_table_pgsoft_game_logs_to_pgsoft3_game_logs_20241024 extends CI_Migration {

    private $tableName='pgsoft3_game_logs';

    public function up() {
        $this->load->model(['player_model']);
        if(!$this->utils->table_really_exists($this->tableName)){
            $this->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like pgsoft_game_logs');
        }
    }

    public function down() {
    	if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}