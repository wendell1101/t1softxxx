<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_oneworks_game_logs_20201209 extends CI_Migration {

    private $tableName = 'oneworks_game_logs';

    public function up() {
        $field = array(
            "esports_gameid" => array(
                "type" => "INT",
                "null" => true
            ),
        );
        $field2 = array(
            "total_score" => array(
                "type" => "INT",
                "null" => true
            ),
        );
        $field3 = array(
            "lottery_bettype" => array(
                "type" => "VARCHAR",
                "constraint" => 360,
                "null" => true
            ),
        );
        if(!$this->db->field_exists('esports_gameid', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
        if(!$this->db->field_exists('total_score', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field2);
        }
        if(!$this->db->field_exists('lottery_bettype', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field3);
        }
    }

    public function down() {

        if($this->db->field_exists('esports_gameid', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'esports_gameid');
        }
        if($this->db->field_exists('total_score', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'total_score');
        }
        if($this->db->field_exists('lottery_bettype', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'lottery_bettype');
        }
    }
}