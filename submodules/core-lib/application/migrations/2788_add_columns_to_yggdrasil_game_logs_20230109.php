<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_yggdrasil_game_logs_20230109 extends CI_Migration
{
	private $tableName = 'yggdrasil_game_logs';


    public function up() {
        $field1 = array(
            'detail' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('detail', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('detail', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'detail');
            }
        }
    }
}
