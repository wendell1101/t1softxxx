<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_to_sbobet_game_logs_v2_20230704 extends CI_Migration {

    private $tableName = 'sbobet_game_logs_v2';

    public function up() {

        $fields = array(
            'username' => array(    
                'type' => 'VARCHAR',            
                'constraint' => '50',
                'null' => true,
            )
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('username', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        
    }
}