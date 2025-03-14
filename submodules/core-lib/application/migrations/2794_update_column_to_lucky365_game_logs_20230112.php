<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_to_lucky365_game_logs_20230112 extends CI_Migration {

    private $tableName = 'lucky365_game_logs';

    public function up() {
        $fieldbetDetail = [
            'betDetail' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        $fieldwinDetail = [
            'winDetail' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('betDetail', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $fieldbetDetail);
            }

            if ($this->db->field_exists('winDetail', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $fieldwinDetail);
            }
        }
    }

    public function down() {
    }
}