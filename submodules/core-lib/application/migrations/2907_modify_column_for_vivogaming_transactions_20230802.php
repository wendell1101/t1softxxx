<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_vivogaming_transactions_20230802 extends CI_Migration {

    private $tableName = 'vivogaming_transactions';

    public function up() {
        $field = [
            'raw_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('raw_data', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}