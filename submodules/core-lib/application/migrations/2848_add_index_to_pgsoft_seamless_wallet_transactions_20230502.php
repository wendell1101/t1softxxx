<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_pgsoft_seamless_wallet_transactions_20230502 extends CI_Migration {

	private $tableName = 'pgsoft_seamless_wallet_transactions';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('transaction_id', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            }
        }
	}

	public function down() {

	}
}