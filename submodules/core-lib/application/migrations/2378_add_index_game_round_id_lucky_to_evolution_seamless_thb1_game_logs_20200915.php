<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_game_round_id_lucky_to_evolution_seamless_thb1_game_logs_20200915 extends CI_Migration {

    private $tableName = 'evolution_seamless_thb1_game_logs';

    public function up()
    {

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_round_id', $this->tableName)){
                # add Index
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_evolution_seamless_thb1_game_logs_game_round_id','game_round_id');
            }
        }

        if($this->utils->table_really_exists('evolution_seamless_thb1_wallet_transactions')){
            if($this->db->field_exists('gameId', 'evolution_seamless_thb1_wallet_transactions') && $this->db->field_exists('action', 'evolution_seamless_thb1_wallet_transactions')){
                # add Index
                $this->load->model('player_model');
                $this->player_model->addIndex('evolution_seamless_thb1_wallet_transactions',
                    'idx_evolution_seamless_thb1_wallet_transactions_gameid_action','gameId, action');
            }
        }

    }

    public function down()
    {
    }
}
