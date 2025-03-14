<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_wcc_game_logs_20240215 extends CI_Migration {

    private $tableName = 'wcc_game_logs';

    public function up() {
        $field = array(
            'game_code' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_code', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                $this->dbforge->add_key('game_code');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_code', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_code');
            }
        }
    }
}