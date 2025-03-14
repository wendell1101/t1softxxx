<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_tag_remarks_20230928 extends CI_Migration {
    
	private $tableName = 'tag_remarks';

	public function up() {
		$fields = array(
			'remarkId' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
            ),
            'tagRemarks' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),            
			'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
		);

		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('remarkId', TRUE);
			$this->dbforge->create_table($this->tableName);
	    }
	}

	public function down() {
		if(!$this->db->table_exists($this->tableName)){
			$this->dbforge->drop_table($this->tableName);
		}
	}
}
