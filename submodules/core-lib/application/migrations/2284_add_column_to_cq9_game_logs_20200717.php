<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cq9_game_logs_20200717 extends CI_Migration {

    private $tableName = 'cq9_game_logs';

    public function up() {

        $fields = array(
            "roomfee" => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            "bettype" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "gameresult" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "tabletype" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "tableid" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
            "roundnumber" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true
            ),
        );

        if(!$this->db->field_exists('roomfee', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('roomfee', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'roomfee');
        }
        if($this->db->field_exists('bettype', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'bettype');
        }
        if($this->db->field_exists('gameresult', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'gameresult');
        }
        if($this->db->field_exists('tabletype', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'tabletype');
        }
        if($this->db->field_exists('tableid', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'tableid');
        }
        if($this->db->field_exists('roundnumber', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'roundnumber');
        }
    }
}