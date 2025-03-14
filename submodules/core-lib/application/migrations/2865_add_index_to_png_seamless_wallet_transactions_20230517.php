<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_png_seamless_wallet_transactions_20230517 extends CI_Migration {

	private $tableName = 'png_seamless_wallet_transactions';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('round_id', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            }
            if( $this->db->field_exists('game_platform_id', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            }
        }
	}

	public function down() {

	}
}