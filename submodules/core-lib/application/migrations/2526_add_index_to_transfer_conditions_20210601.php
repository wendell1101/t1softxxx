<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_transfer_conditions_20210601 extends CI_Migration
{
	private $tableName = 'transfer_conditions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('player_id', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            }
        }
    }

    public function down() {
    }
}