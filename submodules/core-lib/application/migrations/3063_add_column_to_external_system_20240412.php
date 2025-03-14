<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_external_system_20240412 extends CI_Migration {
	private $tableName = 'external_system';

    public function up() {
        $field1 = array(
            'game_platform_order' => array(
				'type' => 'INT',
				'null' => true,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('game_platform_order', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_platform_order', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_platform_order');
            }
        }
    }
}
///END OF FILE/////