<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_fast_track_bonus_crediting_funds_20210811 extends CI_Migration {

    private $tableName = 'fast_track_bonus_crediting_funds';

    public function up() {
        
        $this->utils->debug_log('hahaha');
        $field = array(
            'bonus_type' => array(
                'type' => 'varchar',
                'constraint' => 30,
                'null' => true
            ),
            'cashback_transaction_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonus_type', $this->tableName) && !$this->db->field_exists('cashback_transaction_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonus_type', $this->tableName) && $this->db->field_exists('cashback_transaction_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonus_type');
                $this->dbforge->drop_column($this->tableName, 'cashback_transaction_id');
            }
        }
    }
}