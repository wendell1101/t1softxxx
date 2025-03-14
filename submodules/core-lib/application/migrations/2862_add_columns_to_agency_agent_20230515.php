<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_agency_agent_20230515 extends CI_Migration
{
	private $tableName = 'agency_agents';


    public function up() {
        $field1 = array(
            'remote_wallet_in_maintenance' => array(
                'type' => 'tinyint',
                "null" => false,
				'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('remote_wallet_in_maintenance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_in_maintenance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_in_maintenance');
            }
        }
    }
}
