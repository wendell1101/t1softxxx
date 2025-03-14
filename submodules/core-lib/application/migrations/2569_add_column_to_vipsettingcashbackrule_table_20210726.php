<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_vipsettingcashbackrule_table_20210726 extends CI_Migration {
	private $tableName = 'vipsettingcashbackrule';

	public function up() {

		if (!$this->db->field_exists('cashback_target', $this->tableName)) {
			$field = array(
				'cashback_target' => array(
					'type' => 'INT',
					'null' => false,
					'default' => 1, // CASHBACK_TARGET_PLAYER:1, CASHBACK_TARGET_AFFILIATE:2
				),
			);
			$this->dbforge->add_column($this->tableName, $field);
		}

	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cashback_target', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'cashback_target');
			}
		}
	}
}
