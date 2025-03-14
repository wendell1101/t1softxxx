<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_fg_seamless_thb1_gamelogs_per_transaction_20220203 extends CI_Migration
{
	private $tableName = 'fg_seamless_thb1_gamelogs_per_transaction';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('updated_at', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
            }
        }
    }

    public function down() {
    }
}