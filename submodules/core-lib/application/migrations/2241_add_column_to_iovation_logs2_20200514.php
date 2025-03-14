<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_iovation_logs2_20200514 extends CI_Migration
{
	private $tableName = 'iovation_logs';

    public function up() {

        $fields4 = array(
            'player_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
        );
        

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('player_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields4);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('player_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'player_id');
            }  
        }
    }
}