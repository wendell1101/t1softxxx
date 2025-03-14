<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_BetTradeSuccessDateTime_to_ipm_v2_game_logs_20210528 extends CI_Migration {
    
    private $tableName = 'ipm_v2_game_logs';

    public function up() {
        
        $field1 = array(
            "BetTradeSuccessDateTime" => array(
                'type' => 'datetime',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('BetTradeSuccessDateTime', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $field1);
        }
    }

    public function down() {

        
        if($this->db->field_exists('BetTradeSuccessDateTime', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'BetTradeSuccessDateTime');
        }
    }
}