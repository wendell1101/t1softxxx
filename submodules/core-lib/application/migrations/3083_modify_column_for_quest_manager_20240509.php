<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_quest_manager_20240509 extends CI_Migration {

    private $tableName ='quest_manager';

    public function up() {
        $fields = array(
            'displayPanel' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => TRUE,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('displayPanel', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }
    public function down() {
    }
}
