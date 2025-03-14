<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_player_runtime_20210708 extends CI_Migration
{
	private $tableName = 'player_runtime';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('lastLoginTime', $this->tableName)){

                $this->player_model->addIndex($this->tableName,'idx_lastLoginTime','lastLoginTime');
            }
        }
    }

    public function down() {
    }
}