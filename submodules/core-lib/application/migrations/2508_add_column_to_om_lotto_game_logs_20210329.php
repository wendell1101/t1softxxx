<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_om_lotto_game_logs_20210329 extends CI_Migration {

    private $tableName = 'om_lotto_game_logs';

    public function up() {
        $fields = array(
            'game_type_id' => array(
                'type' => 'TINYINT',
                'null' => true,
            ),
            "game_type_text" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_type_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_type_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_type_id');
            }
            if($this->db->field_exists('game_type_text', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_type_text');
            }
        }
    }
}