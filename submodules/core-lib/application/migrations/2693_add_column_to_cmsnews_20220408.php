<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_cmsnews_20220408 extends CI_Migration
{
    private $tableName = 'cmsnews';

    public function up() {

        $fields = array(
            'detail' => array(
                'type' => 'text',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('detail', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
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