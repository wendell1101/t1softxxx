<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jili_game_logs_20240226 extends CI_Migration {

    private $tableName = 'jili_game_logs';

    public function up() {
        $field_reference_id = array(
            'reference_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('reference_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field_reference_id);
                $this->dbforge->add_key('reference_id');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('reference_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'reference_id');
            }
        }
    }
}
