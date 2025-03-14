<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_promorules_20201002 extends CI_Migration
{
	private $tableName = 'promorules';

    public function up() {

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('claim_bonus_period_type', $this->tableName)){
                $fields = array(
                    'claim_bonus_period_type' => array(
                        'type' => 'TINYINT',
                        'null' => false,
                        'default' => 0
                    ),
                );
                $this->dbforge->add_column($this->tableName, $fields);
            }

            if(!$this->db->field_exists('claim_bonus_period_day', $this->tableName)){
                $fields = array(
                    'claim_bonus_period_day' => array(
                        "type" => "VARCHAR",
                        "constraint" => "25",
                        "null" => true
                    ),
                );
                $this->dbforge->add_column($this->tableName, $fields);
            }

            if(!$this->db->field_exists('claim_bonus_period_date', $this->tableName)){
                $fields = array(
                    'claim_bonus_period_date' => array(
                        "type" => "VARCHAR",
                        "constraint" => "200",
                        "null" => true
                    ),
                );
                $this->dbforge->add_column($this->tableName, $fields);
            }

            if(!$this->db->field_exists('claim_bonus_period_from_time', $this->tableName)){
                $fields = array(
                    'claim_bonus_period_from_time' => array(
                        "type" => "TIME",                        
                        "null" => true,
                        "default" => "00:00:00"
                    ),
                );
                $this->dbforge->add_column($this->tableName, $fields);
            }

            if(!$this->db->field_exists('claim_bonus_period_to_time', $this->tableName)){
                $fields = array(
                    'claim_bonus_period_to_time' => array(
                        "type" => "TIME",                        
                        "null" => true,
                        "default" => "23:59:59"
                    ),
                );
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('claim_bonus_period_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_bonus_period_type');
            }
            if($this->db->field_exists('claim_bonus_period_day', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_bonus_period_day');
            }
            if($this->db->field_exists('claim_bonus_period_date', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_bonus_period_date');
            }
            if($this->db->field_exists('claim_bonus_period_from_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_bonus_period_from_time');
            }
            if($this->db->field_exists('claim_bonus_period_to_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'claim_bonus_period_to_time');
            }
        }
    }
}