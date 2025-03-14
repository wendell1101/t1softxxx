<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_hp_2d3d_game_logs_20231221 extends CI_Migration {

    private $tableName = 'hp_2d3d_game_logs';

    public function up() {
        $column = [
            'bet_status_id' => [
                "type" => "INT",
				'null' => true,
            ],
            'bet_result_id' => [
                "type" => "INT",
				'null' => true,
            ],
            'game_type_id' => [
                "type" => "INT",
				'null' => true,
            ],
            'bet_type_id' => [
                "type" => "INT",
				'null' => true,
            ]
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('bet_status_id', $this->tableName) 
                && $this->db->field_exists('bet_result_id', $this->tableName)
                && $this->db->field_exists('game_type_id', $this->tableName)
                && $this->db->field_exists('bet_type_id', $this->tableName))
            {
                $this->dbforge->modify_column($this->tableName, $column);
            }
        }
    }

    public function down() {
    }
}