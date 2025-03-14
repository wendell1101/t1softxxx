<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_shopping_center_20210125 extends CI_Migration {


    private $tableName = 'shopping_center';

	public function up() {
		$fields = array(
			'item_order' => array(
				'type' => 'INT',
				'null' => TRUE,
			),
        );
        if(!$this->db->field_exists('item_order', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
	}

	public function down() {
        if($this->db->field_exists('item_order', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'item_order');
        }
	}
}