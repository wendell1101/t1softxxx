<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promorules_20240105 extends CI_Migration {

    private $tableName = 'promorules';

    public function up() {
        $fields = array(
            'allow_zero_bonus' => array(
                'type' => 'TINYINT',
                'null' => false,
                'constraint' => 1,
                'default' => 0, // allow: 1, disallow: 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('allow_zero_bonus', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('allow_zero_bonus', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'allow_zero_bonus');
            }
        }
    }
}
