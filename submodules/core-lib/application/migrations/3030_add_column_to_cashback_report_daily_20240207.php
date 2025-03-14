<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cashback_report_daily_20240207 extends CI_Migration {
    private $tableName = 'cashback_report_daily';

    public function up() {
        $field = [
            'playerTags' => [
                'type' => 'JSON',
                'null' => true
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');

            if (!$this->db->field_exists('playerTags', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('playerTags', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'playerTags');
            }
        }
    }
}
///END OF FILE/////