<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_idnpoker_game_logs_table_20220628 extends CI_Migration {
    private $tableName = 'idnpoker_game_logs';

    public function up() {

        $field = array(
            "transaction_no" => array(
                "type" => "INT",
                "null" => true
            ),
            "tableno" => array(
                "type" => "INT",
                "null" => true
            ),
            "room" => array(
                'type' => 'INT',
				'null' => true,
            ),
            "curr_bet" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "r_bet" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "hand" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "curr" => array(
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ),
            "curr_player" => array(
                "type" => "VARCHAR",
                "constraint" => "20",
                "null" => true
            ),
            "curr_amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "agent_comission" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "agent_bill" => array(
                "type" => "DOUBLE",
                "null" => true
            ),

        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('transaction_no', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('transaction_no', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'transaction_no');
            }
        }
    }
}
