<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_last_transactions_20220509 extends CI_Migration {

    private $tableName = 'player_last_transactions';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('last_deposit_date', $this->tableName)){
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_last_deposit_date','last_deposit_date');
            }
        }

    }

    public function down() {

    }
}
