<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_sale_orders_player_promo_id_20240711 extends CI_Migration {
	private $tableName = 'sale_orders';

	public function up() {

		$this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('player_promo_id', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_player_promo_id', 'player_promo_id');
            }
        }
	}

	public function down() {

	}
}