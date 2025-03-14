<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_columns_to_game_tags_20230201 extends CI_Migration
{
	private $tableName = 'game_tags';


    public function up() {
        $field1 = array(
            'deleted_at' => array(
                'type' => 'DATETIME',
                'null' => true
            )
        );

        $field2 = array(
            'is_custom' => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('deleted_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }

            if(!$this->db->field_exists('is_custom', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('deleted_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'deleted_at');
            }

            if($this->db->field_exists('is_custom', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'is_custom');
            }
        }
    }
}
