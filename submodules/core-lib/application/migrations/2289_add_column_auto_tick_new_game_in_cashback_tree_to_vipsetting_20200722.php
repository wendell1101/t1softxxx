<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_auto_tick_new_game_in_cashback_tree_to_vipsetting_20200722 extends CI_Migration
{
	private $tableName = 'vipsetting';

    public function up() {

        $fields = array(
            'auto_tick_new_game_in_cashback_tree' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('auto_tick_new_game_in_cashback_tree', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('auto_tick_new_game_in_cashback_tree', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'auto_tick_new_game_in_cashback_tree');
            }
        }
    }
}