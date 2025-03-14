<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_index_to_queue_results_20210525 extends CI_Migration
{
	private $tableName = 'queue_results';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');
            if($this->db->field_exists('params', $this->tableName)){
                $this->player_model->addIndex($this->tableName,'idx_params','params');
            }
        }
    }

    public function down() {
    }
}