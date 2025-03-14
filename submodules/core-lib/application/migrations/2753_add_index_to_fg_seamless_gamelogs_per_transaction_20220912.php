<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_fg_seamless_gamelogs_per_transaction_20220912 extends CI_Migration
{
	private $tableNames = ['fg_seamless_gamelogs_per_transaction', 'fg_seamless_thb1_gamelogs_per_transaction'];

    public function up() {

        foreach($this->tableNames as $tableName){

            if($this->utils->table_really_exists($tableName)){
                $this->load->model('player_model');
                if($this->db->field_exists('category', $tableName)){                
                    $this->player_model->addIndex($tableName,'idx_category','category');
                }
            }
        }
    }

    public function down() {
    }
}