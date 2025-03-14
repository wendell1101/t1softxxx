<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_evolution_seamless_thb1_wallet_transactions_20230706 extends CI_Migration {
    private $tableName = 'evolution_seamless_thb1_wallet_transactions';

    public function up() {
        $fields = [
            'raw_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('raw_data', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('raw_data', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'raw_data');
            }
        }
    }
}