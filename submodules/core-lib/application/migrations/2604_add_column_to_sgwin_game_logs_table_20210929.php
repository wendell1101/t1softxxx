<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sgwin_game_logs_table_20210929 extends CI_Migration {
    private $tableName = 'sgwin_game_logs';

    public function up() {
        
        $field = array(
            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('response_result_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);                
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('response_result_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'response_result_id');
            }
        }
    }
}
