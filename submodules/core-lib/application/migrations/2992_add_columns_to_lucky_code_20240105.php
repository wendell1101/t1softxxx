<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_lucky_code_20240105 extends CI_Migration {

    private $tableName = 'lucky_code';

    public function up() {
        $fields = array(
            'ip' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
            'country' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
            'city' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('ip', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
    }
}
