<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_free_round_bonuses_20240107 extends CI_Migration {

    private $tableName = 'free_round_bonuses';

    public function up() {
        $field = array(
            'raw_data' => array(
                'type' => 'JSON',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('raw_data', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('raw_data', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'raw_data');
            }
        }
    }
}