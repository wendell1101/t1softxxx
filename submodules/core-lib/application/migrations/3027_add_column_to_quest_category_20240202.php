<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_quest_category_20240202 extends CI_Migration {
	private $tableName = 'quest_category';

    public function up() {
        $field1 = array(
            'period' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('period', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('period', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'period');
            }
        }
    }
}
///END OF FILE/////