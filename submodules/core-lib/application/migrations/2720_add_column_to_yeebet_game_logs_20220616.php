<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_yeebet_game_logs_20220616 extends CI_Migration {

    private $tableName = 'yeebet_game_logs';

    public function up() {

        $balance = array(
            "balance" => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );

        $gamestate = array(
            "gamestate" => array(
                "type" => "INT",
                "constraint" => "15",
                "null" => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $balance);
            }
            if(!$this->db->field_exists('gamestate', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $gamestate);
            }
        }
    }

    public function down() {

    }
}