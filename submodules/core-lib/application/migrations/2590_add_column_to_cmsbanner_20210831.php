<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cmsbanner_20210831 extends CI_Migration {

    private $tableName = 'cmsbanner';

    public function up() {
        
        
        $field = array(
            'start_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'end_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('start_at', $this->tableName) && !$this->db->field_exists('end_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('start_at', $this->tableName) && $this->db->field_exists('end_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'start_at');
                $this->dbforge->drop_column($this->tableName, 'end_at');
            }
        }
    }
}