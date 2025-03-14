<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_auto_lift_runtime_flag_to_game_api_update_history_20210618 extends CI_Migration {
    
    private $tableName = 'game_api_update_history';

    public function up() {
        $field = array(
            "auto_lift_runtime_flag" => array(
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => true
            ),
        );

        if(!$this->db->field_exists('auto_lift_runtime_flag', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
    }

    public function down() {

        if($this->db->field_exists('auto_lift_runtime_flag', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'auto_lift_runtime_flag');
        }
        
    }
}