<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ipm_v2_game_logs_20210521 extends CI_Migration {
    
    private $tableName = 'ipm_v2_game_logs';

    public function up() {
        $field = array(
            "ProviderPlayerId" => array(
                "type" => "VARCHAR",
                "constraint" => 20,
                "null" => true
            ),
        );
        $field2 = array(
            "TotalOdds" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
        );
        $field3 = array(
            "IsResettled" => array(
                'type' => 'TINYINT',
                'null' => true,
                'default' => 0
            ),
        );
        $field4 = array(
            "SettlementDateTime" => array(
                'type' => 'datetime',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('ProviderPlayerId', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field);
        }
        if(!$this->db->field_exists('TotalOdds', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field2);
        }
        if(!$this->db->field_exists('IsResettled', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field3);
        }
        if(!$this->db->field_exists('SettlementDateTime', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field4);
        }
    }

    public function down() {

        if($this->db->field_exists('ProviderPlayerId', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'ProviderPlayerId');
        }
        if($this->db->field_exists('TotalOdds', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'TotalOdds');
        }
        if($this->db->field_exists('IsResettled', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'IsResettled');
        }
        if($this->db->field_exists('SettlementDateTime', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'SettlementDateTime');
        }
    }
}