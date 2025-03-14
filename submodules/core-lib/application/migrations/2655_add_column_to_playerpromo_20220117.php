<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playerpromo_20220117 extends CI_Migration {
	private $tableName = 'playerpromo';

    public function up() {
        $fields = array(
            'actionStatus' => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('actionStatus', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('actionStatus', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'actionStatus');
            }
        }
    }
}
///END OF FILE/////