<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_balance_in_ebet_game_logs_20200814 extends CI_Migration
{
	private $tableName = 'ebet_game_logs';

    public function up() {

        $fields = array(
            'balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('balance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'balance');
            }
        }
    }
}