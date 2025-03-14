<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_game_round_id_lucky_to_evolution_seamless_thb1_game_logs_20220815 extends CI_Migration {

    private $tableName = 'evolution_seamless_thb1_wallet_transactions';

    public function up()
    {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transactionRefId', $this->tableName)){
                # add Index
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_transactionRefId','transactionRefId');
            }
        }

    }

    public function down()
    {
    }
}
