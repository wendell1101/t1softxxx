<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_alter_column_on_hkb_game_logs_20211105 extends CI_Migration {

	private $tableName = 'hkb_game_logs';

	public function up() {

        $fields = array(
            'nickname' => array(    
                'type' => 'VARCHAR',            
                'constraint' => '50',
                'null' => true,
            )
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('nickname', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }

	}

	public function down() {}
}