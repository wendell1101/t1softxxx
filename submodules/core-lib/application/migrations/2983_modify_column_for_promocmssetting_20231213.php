<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_promocmssetting_20231213 extends CI_Migration {

    private $tableName ='promocmssetting';

    public function up() {
        $fields = array(
            'promoOrder' => array(
                'type' => 'INT',
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('promoOrder', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }
    public function down() {
    }
}

