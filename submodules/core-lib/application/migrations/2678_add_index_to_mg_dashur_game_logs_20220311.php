<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_mg_dashur_game_logs_20220311 extends CI_Migration
{
	private $tableName = 'mg_dashur_game_logs';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('account_id', $this->tableName)){                                
                $this->player_model->addIndex($this->tableName,'idx_account_id','account_id');
            }
            if($this->db->field_exists('item_id', $this->tableName)){                                
                $this->player_model->addIndex($this->tableName,'idx_item_id','item_id');
            }
        }
    }

    public function down() {
    }
}