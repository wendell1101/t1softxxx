<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_table_t1lottery_transactions_to_bistro_transactions_20211209 extends CI_Migration {

    private $tableName='bistro_transactions';

    public function up() {
        $this->load->model(['player_model']);
        if(!$this->utils->table_really_exists($this->tableName)){
            $this->player_model->runRawUpdateInsertSQL('create table '.$this->tableName.' like t1lottery_transactions');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
        
    }
}