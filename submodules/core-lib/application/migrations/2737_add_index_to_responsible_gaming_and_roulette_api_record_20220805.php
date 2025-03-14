<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_responsible_gaming_and_roulette_api_record_20220805 extends CI_Migration
{
	private $tableName1 = 'responsible_gaming';
    private $tableName2 = 'roulette_api_record';

    public function up() {

        if($this->utils->table_really_exists($this->tableName1)){
            $this->load->model('player_model');
            if($this->db->field_exists('player_id', $this->tableName1)){                
                $this->player_model->addIndex($this->tableName1,'idx_player_id','player_id');
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            $this->load->model('player_model');
            if($this->db->field_exists('player_promo_id', $this->tableName2)){                
                $this->player_model->addIndex($this->tableName2,'idx_player_promo_id','player_promo_id');
            }
        }
    }

    public function down() {
    }
}