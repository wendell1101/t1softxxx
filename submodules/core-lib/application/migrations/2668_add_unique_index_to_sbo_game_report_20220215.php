<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_unique_index_to_sbo_game_report_20220215 extends CI_Migration
{
	private $tableName = 'sbo_game_report';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('ref_no', $this->tableName)){                
                $this->load->model('player_model');
                $this->player_model->addUniqueIndex($this->tableName,'idx_ref_no','ref_no');
            }
        }
    }

    public function down() {
    }
}
