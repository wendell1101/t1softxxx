<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_tag_20230403 extends CI_Migration {

	private $tableName = 'affiliatetaglist';

	public function up() {
		$fields = array(
			'tagColor' => array(
				'type' => 'VARCHAR',
                'constraint' => '12',
				'null' => TRUE,
			),
		);

		if(!$this->db->field_exists('tagColor', $this->tableName)){
			$this->dbforge->add_column($this->tableName, $fields);
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('tagColor', $this->tableName)){
                $this->dbforge->drop_column('affiliatetaglist', 'tagColor');
            }
        }
	}
}
