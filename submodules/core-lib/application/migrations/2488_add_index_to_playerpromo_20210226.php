<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_playerpromo_20210226 extends CI_Migration
{
	private $tableName = 'playerpromo';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('dateProcessed', $this->tableName)){                
                $this->player_model->addIndex($this->tableName,'idx_dateProcessed','dateProcessed');
            }
        }
    }

    public function down() {
    }
}