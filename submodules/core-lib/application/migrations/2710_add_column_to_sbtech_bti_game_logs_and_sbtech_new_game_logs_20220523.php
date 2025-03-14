<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sbtech_bti_game_logs_and_sbtech_new_game_logs_20220523 extends CI_Migration
{
    private $tableName1 = 'sbtech_bti_game_logs';
    private $tableName2 = 'sbtech_new_game_logs';

    public function up() {

        $freebet_amount = array(
            "freebet_amount" => array(
                "type" => "DOUBLE",
                "null" => true,
            )
        );

        $freebet_isriskfreebet = array(
            "freebet_isriskfreebet" => array(
                "type" => "TINYINT",
                "constraint" => "1",
                "null" => true,
            )
        );

        $real_money_amount = array(
            "real_money_amount" => array(
                "type" => "DOUBLE",
                "null" => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName1)){
            if(!$this->db->field_exists('freebet_amount', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $freebet_amount);
            }
            if(!$this->db->field_exists('freebet_isriskfreebet', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $freebet_isriskfreebet);
            }
            if(!$this->db->field_exists('real_money_amount', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $real_money_amount);
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            if(!$this->db->field_exists('freebet_amount', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $freebet_amount);
            }
            if(!$this->db->field_exists('freebet_isriskfreebet', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $freebet_isriskfreebet);
            }
            if(!$this->db->field_exists('real_money_amount', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $real_money_amount);
            }
        }
    }

    public function down() {

    }
}