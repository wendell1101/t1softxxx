<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_playerbankdetails_20210803 extends CI_Migration{

    private $tableName = "playerbankdetails";

    public function up() {
        $column = array(
            "pixType" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            ),
        );

        $column2 = array(
            "pixKey" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('pixType', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
            if(!$this->db->field_exists('pixKey', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('pixType', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'pixType');
            }
            if($this->db->field_exists('pixKey', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'pixKey');
            }
        }
    }
}