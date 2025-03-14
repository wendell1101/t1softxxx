<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_agency_agent_20230121 extends CI_Migration
{
	private $tableName = 'agency_agents';


    public function up() {
        $field1 = array(
            'seamless_gateway_api_white_ip' => array(
                'type' => 'JSON',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('seamless_gateway_api_white_ip', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('seamless_gateway_api_white_ip', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'seamless_gateway_api_white_ip');
            }
        }
    }
}
