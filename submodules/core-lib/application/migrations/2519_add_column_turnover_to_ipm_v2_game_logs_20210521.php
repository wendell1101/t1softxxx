<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_turnover_to_ipm_v2_game_logs_20210521 extends CI_Migration {
    
    private $tableName = 'ipm_v2_game_logs';

    public function up() {
        $field = array(
            "Turnover" => array(
                "type" => "VARCHAR",
                "constraint" => 50,
                "null" => true
            ),
        );
        if(!$this->db->field_exists('Turnover', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
        
    }

    public function down() {

        if($this->db->field_exists('Turnover', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'Turnover');
        }
        
    }
}