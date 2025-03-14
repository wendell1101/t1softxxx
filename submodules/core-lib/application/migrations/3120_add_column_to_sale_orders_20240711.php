<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sale_orders_20240711 extends CI_Migration {
	private $tableName = 'sale_orders';

	public function up() {
		$fields = array(
            'paybus_order_id' => array(
                'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => true,
            ),
        );

		$this->load->model('player_model');
		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('paybus_order_id', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
				$this->player_model->addIndex($this->tableName,'idx_paybus_order_id','paybus_order_id');
			}
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
			if($this->db->field_exists('paybus_order_id', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'paybus_order_id');
			}
		}
	}
}
