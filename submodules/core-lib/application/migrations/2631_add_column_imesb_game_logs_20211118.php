<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_imesb_game_logs_20211118 extends CI_Migration {

	private $tableName = 'imesb_game_logs';

	public function up() {
		//add column
        $fields = array(
            'basetierid' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'basetiercode' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'basetiername' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );
		
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('basetierid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('basetierid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, $this->fields);
            }
        }
	}
}