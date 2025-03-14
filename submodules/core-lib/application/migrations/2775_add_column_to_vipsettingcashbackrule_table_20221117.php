<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_vipsettingcashbackrule_table_20221117 extends CI_Migration {
	private $tableName = 'vipsettingcashbackrule';

	public function up() {

		if (!$this->db->field_exists('enable_vip_downgrade', $this->tableName)) {
			$field = array(
				'enable_vip_downgrade' => array(
					'type' => 'INT',
					'null' => false,
					'default' => 1, // enabled:1, disabled:0
				),
			);
			$this->dbforge->add_column($this->tableName, $field);
		}

	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('enable_vip_downgrade', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'enable_vip_downgrade');
			}
		}
	}
}
