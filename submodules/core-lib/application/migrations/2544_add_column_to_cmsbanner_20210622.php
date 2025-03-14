<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cmsbanner_20210622 extends CI_Migration {
    
    private $tableName = 'cmsbanner';

    public function up() {
        $field = array(
            'sort_order' => array(
                'type' => 'int',
                'default' => 0,
            ),
        );

        if(!$this->db->field_exists('sort_order', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
    }

    public function down() {
        if($this->db->field_exists('sort_order', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'sort_order');
        }

    }
}