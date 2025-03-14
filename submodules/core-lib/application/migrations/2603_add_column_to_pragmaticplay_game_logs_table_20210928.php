<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pragmaticplay_game_logs_table_20210928 extends CI_Migration {
    private $tableName = 'pragmaticplay_game_logs';

    public function up() {
        
        $field = array(
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);                
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'after_balance');
            }
        }
    }
}
