<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_field_external_common_tokens_20210310 extends CI_Migration {

	private $tableName = 'external_common_tokens';

	public function up() {

		$fields = array(
			'extra_info' => array(
				'type' => 'JSON',
				'null' => true
			)
		);

		if (!$this->db->field_exists('extra_info', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $fields);
		}
	
	}

    public function down(){
		$this->dbforge->drop_column($this->tableName, 'extra_info');
    }
}