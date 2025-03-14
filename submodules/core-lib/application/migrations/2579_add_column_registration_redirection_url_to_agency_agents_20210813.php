<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_registration_redirection_url_to_agency_agents_20210813 extends CI_Migration {

	private $tableName = 'agency_agents';

	public function up() {
		

        $fields = array(
            'registration_redirection_url' => array(
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' => true,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('registration_redirection_url', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('registration_redirection_url', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'vip_level_info');
            }
        }
	}
}

///END OF FILE//////////