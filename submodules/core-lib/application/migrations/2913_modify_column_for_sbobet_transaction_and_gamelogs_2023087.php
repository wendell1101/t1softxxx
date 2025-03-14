<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_sbobet_transaction_and_gamelogs_2023087 extends CI_Migration {

    private $trans_table='sbobet_seamless_game_transactions';    
    private $game_logs_table='sbobet_seamless_game_logs';  

    public function up() {
        $field1 = array(
            'unique_transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
        );
        $field2 = array(
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '300',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->trans_table)){
            if($this->db->field_exists('unique_transaction_id', $this->trans_table)){
                $this->dbforge->modify_column($this->trans_table, $field1);
            }
        }
        if($this->utils->table_really_exists($this->game_logs_table)){
            if($this->db->field_exists('external_uniqueid', $this->game_logs_table)){
                $this->dbforge->modify_column($this->game_logs_table, $field2);
            }
        }

    }

    public function down() {
    }
}