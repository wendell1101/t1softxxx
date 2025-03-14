<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fast_track_bonus_crediting_20210723 extends CI_Migration {

    private $tableName = 'fast_track_bonus_crediting';

    public function up() {
        $field = array(
            'bonus_code' => array(
                'type' => 'varchar',
                'constraint' => 30,
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonus_code', $this->tableName)){  
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonus_code', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonus_code');
            }
        }
    }
}