<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sbtech_new_game_logs_20240614 extends CI_Migration {
    private $tableName = 'sbtech_new_game_logs';

    public function up() {
        $field1 = array(
            'combo_bonus_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if( ! $this->db->field_exists('combo_bonus_amount', $this->tableName) ){
                $this->dbforge->add_column($this->tableName, $field1);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('combo_bonus_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'combo_bonus_amount');
            }
        }
    }
}