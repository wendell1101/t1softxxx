<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_extra_info_to_payment_account_20230215 extends CI_Migration {

	private $tableName = 'payment_account';

	public function up() {

		$field1 = array(
            'extra_info' => array(
                'type' => 'JSON',
                'null' => true
            )
        );


		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('extra_info', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $field1);
			}
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
			if($this->db->field_exists('extra_info', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'extra_info');
			}
		}
	}
}

///END OF FILE//////////