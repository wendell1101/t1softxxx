<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pragmaticplay_livedealer_20210603 extends CI_Migration {

    private $tables = ['pragmaticplay_livedealer_cny1_gamelogs','pragmaticplay_livedealer_idr1_gamelogs','pragmaticplay_livedealer_myr1_gamelogs','pragmaticplay_livedealer_thb1_gamelogs','pragmaticplay_livedealer_usd1_gamelogs','pragmaticplay_livedealer_vnd1_gamelogs'];

    public function up() {
        $field = array(
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)){
                if(!$this->db->field_exists('after_balance', $table)){
                    $this->dbforge->add_column($table, $field);
                }
            }
        }  
    }

    public function down() {
        foreach ($this->tables as $table) {
            if($this->utils->table_really_exists($table)){
                if($this->db->field_exists('after_balance', $table)){
                    $this->dbforge->drop_column($table, 'after_balance');
                }
            }
        } 
    }
}