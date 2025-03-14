<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_imesb_game_logs_table_20211028 extends CI_Migration {
    private $tableName = 'imesb_game_logs';

    public function up() {
        
        $field = array(
            "operatorid" => array(
                "type" => "INT",
                "null" => true
            ),
            "eventid" => array(
                "type" => "INT",
                "null" => true
            ),
            "eventname" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "eventname" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "betstatus" => array(
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ),
            "betdescription" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "marketdate" => array(
                "type" => "DATETIME",
                "null" => true
            ),
            "betoutcome" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('operatorid', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);                
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('operatorid', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'operatorid');
            }
        }
    }
}
