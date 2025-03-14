<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_agency_agent_202301171415 extends CI_Migration
{
	private $tableName = 'agency_agents';


    public function up() {
        $field1 = array(
            'remote_wallet_proxy' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('remote_wallet_proxy', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_proxy', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_proxy');
            }
        }
    }
}
