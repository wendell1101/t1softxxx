<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_bet_choice_for_oneworks_game_logs_20210210 extends CI_Migration {

    private $tableName='oneworks_game_logs';

    public function up() {
        $field = array(
            "bet_choice" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,    
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bet_choice', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->db->field_exists('bet_choice', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'bet_choice');
        }
    }
}