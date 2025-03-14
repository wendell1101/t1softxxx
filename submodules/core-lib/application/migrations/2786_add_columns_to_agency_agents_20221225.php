<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_agency_agents_20221225 extends CI_Migration
{
	private $tableName = 'agency_agents';


    public function up() {
        $field1 = array(
            'remote_wallet_auth_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );
        $field2 = array(
            'remote_wallet_sign_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );
        $field3 = array(
            'remote_wallet_url' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('remote_wallet_auth_token', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('remote_wallet_sign_key', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('remote_wallet_url', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_auth_token', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_auth_token');
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_sign_key', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_sign_key');
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('remote_wallet_url', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_url');
            }
        }
    }
}
