<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_on_casino_game_logs_20240701 extends CI_Migration {
    private $tableName = 'on_casino_game_logs';

    public function up() {
        $field1 = array(
            'net_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
        );
        $field2 = array(
            'readable_add_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );
        $field3 = array(
            'readable_open_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );
        $field4 = array(
            'readable_settle_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );
        $field5 = array(
            'update_time' => array(
                'type' => 'TIMESTAMP',
                'null' => true,
			),
        );
        $field6= array(
            'readable_update_time' => array(
                'type' => 'DATETIME',
                'null' => true,
			),
        );
      

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('net_amount', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if( ! $this->db->field_exists('readable_add_time', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if( ! $this->db->field_exists('readable_open_time', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field3);
            }
            if( ! $this->db->field_exists('readable_settle_time', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field4);
            }
            if( ! $this->db->field_exists('update_time', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field5);
            }
            if( ! $this->db->field_exists('readable_update_time', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field6);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('net_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'net_amount');
            }
            if($this->db->field_exists('readable_add_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'readable_add_time');
            }
            if($this->db->field_exists('readable_open_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'readable_open_time');
            }
            if($this->db->field_exists('readable_settle_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'readable_settle_time');
            }
            if($this->db->field_exists('update_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'update_time');
            }
            if($this->db->field_exists('readable_update_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'readable_update_time');
            }
        }
    }
}