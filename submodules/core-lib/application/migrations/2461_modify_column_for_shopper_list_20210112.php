<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_shopper_list_20210112 extends CI_Migration {

    private $tableName='shopper_list';

    public function up() {
        $field = array(
            'required_points' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('required_points', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}